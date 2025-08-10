<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Collection\Collection;
use Cake\Event\EventInterface;
use Cake\Filesystem\Folder;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Portfolios Controller
 *
 * @property \App\Model\Table\PortfoliosTable $Portfolios
 * @method \App\Model\Entity\Portfolio[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class PortfoliosController extends AppController
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        // indexアクションだけログイン不要にする
        $this->Authentication->addUnauthenticatedActions(['search']);
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    // src/Controller/PortfoliosController.php

    public function index()
    {
        $this->loadModel('Likes');
        $this->loadModel('Portfolios');
    
        $identity = $this->request->getAttribute('identity');
        $userId = $identity ? $identity->get('id') : null;
    
        $portfolios = $this->Portfolios->find()
            ->contain(['Users'])
            ->where(['is_public' => true])
            ->order(['created' => 'DESC'])
            ->limit(10)
            ->toArray();
    
        foreach ($portfolios as $p) {
            $p->like_count = $this->Likes->find()
                ->where(['portfolio_id' => $p->id])
                ->count();
    
            // ✅ 自分がいいねしてるかチェック
            $p->liked_by_me = false;
            if ($userId !== null) {
                $p->liked_by_me = $this->Likes->exists([
                    'user_id' => $userId,
                    'portfolio_id' => $p->id
                ]);
            }
        }
    
        $this->set(compact('portfolios'));
    }
    

    /**
     * View method
     *
     * @param string|null $id Portfolio id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $this->loadModel('Follows');
        $this->loadModel('Comments');
    
        // ポートフォリオ取得（投稿者情報、カテゴリ、コメント含む）
        $portfolio = $this->Portfolios->get($id, [
            'contain' => ['Users', 'Categories', 'Comments' => ['Users']],
        ]);
    
        // 非公開チェック（投稿者本人以外アクセス禁止）
        if (!$portfolio->is_public && $portfolio->user_id !== $this->request->getAttribute('identity')->get('id')) {
            $this->Flash->error('この投稿にはアクセスできません。');
            return $this->redirect(['action' => 'index']);
        }
    
        // コメント取得（並び順：新しい順）
        $comments = $this->Comments->find()
            ->where(['portfolio_id' => $id])
            ->contain(['Users'])
            ->order(['created' => 'DESC'])
            ->toArray();
    
        // 投稿者のユーザーID
        $userId = $portfolio->user_id;
    
        // ログイン中のユーザーID
        $authId = $this->request->getAttribute('identity')->get('id');
    
        // フォロー数／フォロワー数
        $followerCount = $this->Follows->find()
            ->where(['followed_id' => $userId])
            ->count();
    
        $followingCount = $this->Follows->find()
            ->where(['follower_id' => $userId])
            ->count();
    
        // ログイン中ユーザーがフォローしているか
        $isFollowing = false;
        if ($authId && $authId != $userId) {
            $isFollowing = $this->Follows->exists([
                'follower_id' => $authId,
                'followed_id' => $userId
            ]);
        }
    
        $this->set(compact('portfolio', 'comments', 'followerCount', 'followingCount', 'isFollowing'));
    }    

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $portfolio = $this->Portfolios->newEmptyEntity();
    
        if ($this->request->is('post')) {
            $data = $this->request->getData();
    
            // --- サムネ画像（既存ロジックそのまま/少し安全化） ---
            $thumbnailFile = $this->request->getData('thumbnail_file');
            if ($thumbnailFile && $thumbnailFile->getError() === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($thumbnailFile->getClientFilename(), PATHINFO_EXTENSION));
                $safeExt = in_array($ext, ['jpg','jpeg','png','webp'], true) ? $ext : 'jpg';
                $filename = Text::uuid() . '.' . $safeExt;
                $uploadPath = WWW_ROOT . 'uploads' . DS . $filename;
                $thumbnailFile->moveTo($uploadPath);
                $data['thumbnail'] = '/uploads/' . $filename;
            }
    
            // --- エンティティに反映 ---
            $portfolio = $this->Portfolios->patchEntity($portfolio, $data);
            $portfolio->user_id = $this->request->getAttribute('identity')->get('id');
    
            // 先に保存してIDを確定（保存後に /files/portfolios/{id}/ に置く）
            if ($this->Portfolios->save($portfolio)) {
                try {
                    $this->handlePdfUploads($portfolio); // ← PDF保存＋パス更新（下に定義）
    
                    $this->Flash->success('投稿が完了しました！');
                    return $this->redirect(['controller' => 'Top', 'action' => 'index']);
                } catch (\Throwable $e) {
                    // PDF保存で失敗した場合
                    $this->Flash->error('ファイル保存でエラーが発生しました：' . $e->getMessage());
                }
            } else {
                $this->Flash->error('投稿に失敗しました。もう一度お試しください。');
            }
        }
    
        // categories に slug も含めて渡す（既存）
        $categories = $this->Portfolios->Categories->find()
            ->select(['id', 'name', 'slug'])
            ->order(['id' => 'ASC'])
            ->all()
            ->toArray();
    
        $this->set(compact('portfolio', 'categories'));
    }   

    /**
     * Edit method
     *
     * @param string|null $id Portfolio id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        // ✅ カテゴリ情報も一緒に取得！
        $portfolio = $this->Portfolios->get($id, [
            'contain' => ['Categories']
        ]);
    
        if ($portfolio->user_id !== $this->request->getAttribute('identity')->getIdentifier()) {
            $this->Flash->error('この投稿を編集する権限がありません。');
            return $this->redirect(['controller' => 'Users', 'action' => 'profile']);
        }
    
        if ($this->request->is(['patch', 'post', 'put'])) {
            $portfolio = $this->Portfolios->patchEntity($portfolio, $this->request->getData());
            if ($this->Portfolios->save($portfolio)) {
                $this->Flash->success(__('投稿が更新されました。'));
                return $this->redirect(['controller' => 'Users', 'action' => 'profile']);
            }
            $this->Flash->error(__('投稿の更新に失敗しました。'));
        }
    
        $this->set(compact('portfolio'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Portfolio id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id)
    {
        $portfolio = $this->Portfolios->get($id);
        $userId = $this->request->getAttribute('identity')->get('id');
    
        // 他人の投稿は削除させない
        if ($portfolio->user_id !== $userId) {
            throw new \Cake\Http\Exception\ForbiddenException('この投稿を削除する権限がありません');
        }
    
        // POSTメソッドのみ許可
        $this->request->allowMethod(['post', 'delete']);
    
        if ($this->Portfolios->delete($portfolio)) {
            $this->Flash->success('投稿を削除しました');
        } else {
            $this->Flash->error('投稿の削除に失敗しました');
        }
    
        return $this->redirect(['controller' => 'Users', 'action' => 'profile']);
    }
    

    /**
     * 公開・非公開アクション
     */
    public function togglePublic($id)
    {
        $portfolio = $this->Portfolios->get($id);
        if ($portfolio->user_id !== $this->request->getAttribute('identity')->get('id')) {
            throw new ForbiddenException();
        }

        $portfolio->is_public = !$portfolio->is_public;
        $this->Portfolios->save($portfolio);

        return $this->redirect(['controller' => 'Users', 'action' => 'profile']);
    }

    public function search()
    {
        $this->request->allowMethod(['get']);
        $keyword = $this->request->getQuery('q');

        $query = $this->Portfolios->find()
            ->contain(['Users']) // 投稿者名なども後で使いたければ
            ->where(['is_public' => true]);

        if (!empty($keyword)) {
            $query->andWhere([
                'OR' => [
                    'Portfolios.title LIKE' => '%' . $keyword . '%',
                    'Portfolios.description LIKE' => '%' . $keyword . '%',
                ]
            ]);
        }

        $portfolios = $query->order(['Portfolios.created' => 'DESC'])->toArray();

        $this->set(compact('portfolios', 'keyword'));
        $this->render('index'); // ← トップページ（index）テンプレートを再利用
    }

    private function savePdfUploads(\App\Model\Entity\Portfolio $portfolio): void
    {
        $request = $this->request;
        $files = [];

        // 単体：図面
        $drawing = $request->getData('drawing_pdf');
        if ($drawing && $drawing->getError() === UPLOAD_ERR_OK) {
            $files[] = ['file' => $drawing, 'kind' => 'drawing'];
        }

        // 複数：補足
        $supps = (array)$request->getData('supplement_pdfs');
        foreach ($supps as $f) {
            if ($f && $f->getError() === UPLOAD_ERR_OK) {
                $files[] = ['file' => $f, 'kind' => 'supplement'];
            }
        }

        if (!$files) return;

        $baseDir = WWW_ROOT . 'files' . DS . 'portfolios' . DS . $portfolio->id . DS;
        (new Folder($baseDir, true, 0755));

        foreach ($files as $item) {
            $f = $item['file'];

            // サーバ側バリデーション（拡張子だけでなくMIMEも確認）
            if (strtolower(pathinfo($f->getClientFilename(), PATHINFO_EXTENSION)) !== 'pdf') {
                throw new \RuntimeException('PDFのみアップロードできます。');
            }
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($f->getStream()->getContents());
            $f->getStream()->rewind();
            if (!in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
                throw new \RuntimeException('PDF以外のファイルです。');
            }
            if ($f->getSize() > 20 * 1024 * 1024) { // 20MB
                throw new \RuntimeException('ファイルサイズは20MBまでです。');
            }

            // 衝突しない安全なファイル名に
            $safe = 'p-' . $portfolio->id . '-' . $item['kind'] . '-' . bin2hex(random_bytes(8)) . '.pdf';
            $f->moveTo($baseDir . $safe);

            // 簡易的にポートフォリオのフィールドへ格納（最小構成）
            if ($item['kind'] === 'drawing') {
                $portfolio->drawing_pdf_path = 'files/portfolios/' . $portfolio->id . '/' . $safe;
            } else {
                // 複数は配列→JSONで持つ
                $list = (array)($portfolio->supplement_pdf_paths ?? []);
                $list[] = 'files/portfolios/' . $portfolio->id . '/' . $safe;
                $portfolio->supplement_pdf_paths = $list;
            }
        }
    }

    /**
     * PDF 1ファイルを検証して保存し、保存したファイル名を返す
     */
    private function moveOnePdf(\Psr\Http\Message\UploadedFileInterface $file, string $baseDir, string $kind, int $pid): string
    {
        // 拡張子チェック（フロントの accept だけでは不十分）
        $ext = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            throw new \RuntimeException('PDFのみアップロードできます。');
        }

        // MIME検証（finfo）※ stream を一度読み込んでから rewind 必須
        $stream = $file->getStream();
        $contents = $stream->getContents();
        $stream->rewind();

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($contents);
        if (!in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
            throw new \RuntimeException('PDF以外のファイルです。');
        }

        // サイズ上限（例：20MB）
        if ($file->getSize() > 20 * 1024 * 1024) {
            throw new \RuntimeException('ファイルサイズは20MBまでです。');
        }

        // ランダムな安全ファイル名
        $safeName = sprintf('p-%d-%s-%s.pdf', $pid, $kind, bin2hex(random_bytes(8)));
        $file->moveTo($baseDir . $safeName);

        return $safeName;
    }



}
