<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Utility\Text;
use Cake\Event\EventInterface;
use Cake\Filesystem\Folder;
use Psr\Http\Message\UploadedFileInterface;

class PortfoliosController extends AppController
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        // 公開一覧・公開詳細・検索は未ログインでもOK
        $this->Authentication->addUnauthenticatedActions(['index', 'view', 'search']);
    }

    public function index()
    {
        $this->loadModel('Likes');

        $identity   = $this->request->getAttribute('identity');
        $authUserId = $identity ? $identity->get('id') : null;

        $portfolios = $this->Portfolios->find()
            ->contain(['Users']) // 必要なら 'Companies' も
            ->where(['Portfolios.is_public' => true])
            ->order(['Portfolios.created' => 'DESC'])
            ->limit(10)
            ->toArray();

        foreach ($portfolios as $p) {
            $p->like_count = $this->Likes->find()->where(['portfolio_id' => $p->id])->count();
            $p->liked_by_me = $authUserId
                ? $this->Likes->exists(['user_id' => $authUserId, 'portfolio_id' => $p->id])
                : false;
        }

        $this->set(compact('portfolios'));
    }

    public function view($id = null)
    {
        $this->loadModel('Follows');
        $this->loadModel('Comments');

        // 会社情報もビューファイルで使うなら 'Companies' を含める
        $portfolio = $this->Portfolios->get($id, [
            'contain' => ['Users', 'Companies', 'Categories', 'Comments' => ['Users']],
        ]);

        // 認証ID（未ログインなら null）
        $identity   = $this->request->getAttribute('identity');
        $authUserId = $identity ? $identity->get('id') : null;

        // 非公開は本人のみ（ユーザー投稿想定）
        if (!$portfolio->is_public) {
            if ($authUserId === null || (int)$portfolio->user_id !== (int)$authUserId) {
                $this->Flash->error('この投稿にはアクセスできません。');
                return $this->redirect(['action' => 'index']);
            }
        }

        // ===== フォローUIはユーザー投稿のときだけ =====
        $showFollowUi   = false;
        $followerCount  = 0;
        $followingCount = 0;
        $isFollowing    = false;

        if (!empty($portfolio->user_id)) {
            $showFollowUi = true;
            $targetUserId = (int)$portfolio->user_id;

            // null を where に入れない！ user_id がある場合だけ集計
            $followerCount = $this->Follows->find()
                ->where(['followed_id' => $targetUserId])
                ->count();

            $followingCount = $this->Follows->find()
                ->where(['follower_id' => $targetUserId])
                ->count();

            if ($authUserId !== null && (int)$authUserId !== $targetUserId) {
                $isFollowing = $this->Follows->exists([
                    'follower_id' => (int)$authUserId,
                    'followed_id' => $targetUserId,
                ]);
            }
        }
        // ===== 会社投稿なら上の if に入らないので NULL 条件は一切発生しない =====

        // コメント（新しい順）
        $Comments = $this->fetchTable('Comments');
        $comments = $Comments->find()
            ->where(['portfolio_id' => $portfolio->id])
            ->contain(['Users', 'Companies'])
            ->order(['Comments.created' => 'ASC'])
            ->all();
        $this->set(compact('comments'));


        $this->set(compact(
            'portfolio',
            'comments',
            'showFollowUi',
            'followerCount',
            'followingCount',
            'isFollowing'
        ));
    }

    public function add()
    {
        $portfolio = $this->Portfolios->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // ユーザー投稿として確定
            $identity = $this->Authentication->getIdentity();
            if ($identity) {
                $portfolio->user_id = $identity->get('id');
                $portfolio->company_id = null;
            }

            // サムネ（任意）
            $thumbnailFile = $this->request->getData('thumbnail_file');
            if ($thumbnailFile && $thumbnailFile->getError() === UPLOAD_ERR_OK) {
                $ext     = strtolower(pathinfo($thumbnailFile->getClientFilename(), PATHINFO_EXTENSION));
                $safeExt = in_array($ext, ['jpg','jpeg','png','webp'], true) ? $ext : 'jpg';
                $filename   = Text::uuid() . '.' . $safeExt;
                $uploadPath = WWW_ROOT . 'uploads' . DS . $filename;
                $thumbnailFile->moveTo($uploadPath);
                $data['thumbnail'] = '/uploads/' . $filename;
            }

            $portfolio = $this->Portfolios->patchEntity($portfolio, $data);

            if ($this->Portfolios->save($portfolio)) {
                try {
                    $this->handlePdfUploads($portfolio);
                } catch (\Throwable $e) {
                    $this->Flash->error('ファイル保存でエラーが発生しました：' . $e->getMessage());
                }
                $this->Flash->success('投稿が完了しました！');
                return $this->redirect(['action' => 'view', $portfolio->id]);
            }

            $this->Flash->error('投稿に失敗しました。もう一度お試しください。');
        }

        // フォーム用カテゴリ（slug 付き）
        $categories = $this->Portfolios->Categories->find()
            ->select(['id', 'name', 'slug'])
            ->order(['id' => 'ASC'])
            ->all()
            ->toArray();

        $this->set(compact('portfolio', 'categories'));
        $this->viewBuilder()->setTemplatePath('Portfolios');
        $this->render('add');
    }

    public function edit($id = null)
    {
        $portfolio = $this->Portfolios->get($id, [
            'contain' => ['Categories'],
        ]);

        // 自分の投稿しか編集できない
        $identity   = $this->request->getAttribute('identity');
        $authUserId = $identity ? $identity->get('id') : null;

        if (!$authUserId || (int)$portfolio->user_id !== (int)$authUserId) {
            $this->Flash->error('この投稿を編集する権限がありません。');
            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // サムネ差し替え（任意）
            $thumbnailFile = $this->request->getData('thumbnail_file');
            if ($thumbnailFile && $thumbnailFile->getError() === UPLOAD_ERR_OK) {
                $ext     = strtolower(pathinfo($thumbnailFile->getClientFilename(), PATHINFO_EXTENSION));
                $safeExt = in_array($ext, ['jpg','jpeg','png','webp'], true) ? $ext : 'jpg';
                $filename   = Text::uuid() . '.' . $safeExt;
                $uploadPath = WWW_ROOT . 'uploads' . DS . $filename;
                $thumbnailFile->moveTo($uploadPath);
                $data['thumbnail'] = '/uploads/' . $filename;
            }

            $portfolio = $this->Portfolios->patchEntity($portfolio, $data);

            if ($this->Portfolios->save($portfolio)) {
                try {
                    $this->handlePdfUploads($portfolio);
                } catch (\Throwable $e) {
                    $this->Flash->error('ファイル保存でエラーが発生しました：' . $e->getMessage());
                }
                $this->Flash->success('投稿が更新されました。');
                return $this->redirect(['action' => 'view', $id]);
            }

            $this->Flash->error('投稿の更新に失敗しました。');
        }

        $this->set(compact('portfolio'));
        $this->viewBuilder()->setTemplatePath('Portfolios');
        $this->render('add'); // add フォーム再利用
    }

    public function delete($id)
    {
        $portfolio  = $this->Portfolios->get($id);
        $identity   = $this->request->getAttribute('identity');
        $authUserId = $identity ? $identity->get('id') : null;

        if ((int)$portfolio->user_id !== (int)$authUserId) {
            throw new \Cake\Http\Exception\ForbiddenException('この投稿を削除する権限がありません');
        }

        $this->request->allowMethod(['post', 'delete']);

        if ($this->Portfolios->delete($portfolio)) {
            $this->Flash->success('投稿を削除しました');
        } else {
            $this->Flash->error('投稿の削除に失敗しました');
        }

        return $this->redirect(['controller' => 'Users', 'action' => 'profile']);
    }

    public function togglePublic($id)
    {
        $portfolio = $this->Portfolios->get($id);
        $identity  = $this->request->getAttribute('identity');
        $authUserId = $identity ? $identity->get('id') : null;

        if ((int)$portfolio->user_id !== (int)$authUserId) {
            throw new \Cake\Http\Exception\ForbiddenException();
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
            ->contain(['Users'])
            ->where(['Portfolios.is_public' => true]);

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
        $this->render('index');
    }

    /** -------- PDF ヘルパ -------- */

    private function moveOnePdf(UploadedFileInterface $file, string $baseDir, string $kind, int $pid): string
    {
        $ext = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            throw new \RuntimeException('PDFのみアップロードできます。');
        }

        $stream   = $file->getStream();
        $contents = $stream->getContents();
        $stream->rewind();

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->buffer($contents);
        if (!in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
            throw new \RuntimeException('PDF以外のファイルです。');
        }

        if ($file->getSize() > 20 * 1024 * 1024) {
            throw new \RuntimeException('ファイルサイズは20MBまでです。');
        }

        $safeName = sprintf('p-%d-%s-%s.pdf', $pid, $kind, bin2hex(random_bytes(8)));
        $file->moveTo($baseDir . $safeName);

        return $safeName;
    }

    private function handlePdfUploads(\App\Model\Entity\Portfolio $portfolio): void
    {
        $req     = $this->request;
        $drawing = $req->getData('drawing_pdf');
        $supps   = (array)$req->getData('supplement_pdfs');

        if (!$drawing && empty(array_filter($supps))) {
            return;
        }

        $baseDir = WWW_ROOT . 'files' . DS . 'portfolios' . DS . $portfolio->id . DS;
        (new Folder($baseDir, true, 0755));

        if ($drawing && $drawing->getError() === UPLOAD_ERR_OK) {
            $fname = $this->moveOnePdf($drawing, $baseDir, 'drawing', (int)$portfolio->id);
            $portfolio->drawing_pdf_path = 'files/portfolios/' . $portfolio->id . '/' . $fname;
        }

        $suppPaths = [];
        foreach ($supps as $f) {
            if ($f && $f->getError() === UPLOAD_ERR_OK) {
                $fname = $this->moveOnePdf($f, $baseDir, 'supplement', (int)$portfolio->id);
                $suppPaths[] = 'files/portfolios/' . $portfolio->id . '/' . $fname;
            }
        }

        if ($suppPaths) {
            $current    = $portfolio->supplement_pdf_paths;
            $currentArr = is_string($current) ? (array)json_decode($current, true) : (array)$current;
            $portfolio->supplement_pdf_paths = array_values(array_merge($currentArr, $suppPaths));
        }

        if (is_array($portfolio->supplement_pdf_paths)) {
            $portfolio->supplement_pdf_paths = json_encode($portfolio->supplement_pdf_paths, JSON_UNESCAPED_SLASHES);
        }

        if (!$this->Portfolios->save($portfolio)) {
            throw new \RuntimeException('ファイルパスの保存に失敗しました。');
        }
    }
}
