<?php
declare(strict_types=1);

namespace App\Controller\Employer;

use App\Controller\AppController;
use Cake\Utility\Text;
use Cake\Filesystem\Folder;
use Psr\Http\Message\UploadedFileInterface;
use Cake\Log\Log;

class PortfoliosController extends AppController
{
    public function add()
    {
        $this->request->allowMethod(['get', 'post']);

        $Categories = $this->fetchTable('Categories');

        // セレクト用: id => name
        $categoryOptions = $Categories->find('list', [
            'keyField'   => 'id',
            'valueField' => 'name',
        ])->toArray();

        // JS用: id => slug（slug が null のものは除外）
        $slugMap = $Categories->find()
            ->select(['id', 'slug'])
            ->where(['slug IS NOT' => null])
            ->all()
            ->combine('id', 'slug')
            ->toArray();

        $Portfolios = $this->fetchTable('Portfolios');
        $portfolio  = $Portfolios->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // サムネイル（任意）
            $thumbnailFile = $this->request->getData('thumbnail_file');
            if ($thumbnailFile && $thumbnailFile->getError() === UPLOAD_ERR_OK) {
                $ext     = strtolower(pathinfo($thumbnailFile->getClientFilename(), PATHINFO_EXTENSION));
                $safeExt = in_array($ext, ['jpg','jpeg','png','webp'], true) ? $ext : 'jpg';
                $filename   = Text::uuid() . '.' . $safeExt;
                $uploadPath = WWW_ROOT . 'uploads' . DS . $filename;
                $thumbnailFile->moveTo($uploadPath);
                $data['thumbnail'] = '/uploads/' . $filename;
            }

            // 会社認証での投稿（user_id は null）
            $identity  = $this->Authentication->getIdentity();
            $companyId = $identity ? ($identity->get('company_id') ?? ($identity->get('company')['id'] ?? $identity->get('id'))) : null;

            $portfolio = $Portfolios->patchEntity($portfolio, $data);
            $portfolio->company_id = $companyId;
            $portfolio->user_id    = null;

            if ($Portfolios->save($portfolio)) {
                try {
                    $this->handlePdfUploads($portfolio);
                    $this->Flash->success('企業として投稿しました。');
                    // 作成直後の詳細へ
                    return $this->redirect(['action' => 'view', $portfolio->id]);
                } catch (\Throwable $e) {
                    $this->Flash->error('ファイル保存でエラーが発生しました：' . $e->getMessage());
                    // 失敗してもレコード自体は作成済みなので view に飛ばす選択も可
                    return $this->redirect(['action' => 'view', $portfolio->id]);
                }
            }

            $this->Flash->error('投稿に失敗しました。入力内容をご確認ください。');
            Log::error('Employer portfolio save failed: ' . print_r($portfolio->getErrors(), true));
            Log::debug('POST data: ' . print_r($this->request->getData(), true));
            Log::debug('Identity: ' . print_r($identity, true));
        }

        $this->set(compact('portfolio', 'categoryOptions', 'slugMap'));
        $this->viewBuilder()->setTemplatePath('Portfolios');
        $this->render('add');
    }

    public function view($id = null)
    {
        $this->loadModel('Follows');
        $this->loadModel('Comments');

        $Portfolios = $this->fetchTable('Portfolios');

        $portfolio = $Portfolios->get($id, [
            'contain' => ['Users', 'Companies', 'Categories', 'Comments' => ['Users']],
        ]);

        // 認証情報
        $identity       = $this->request->getAttribute('identity');
        $authUserId     = $identity ? $identity->get('id') : null;
        $authCompanyId  = $identity ? ($identity->get('company_id') ?? null) : null;

        // 非公開なら本人（ユーザーor会社）だけOK
        if (!$portfolio->is_public) {
            $isOwner = false;
            if ($portfolio->user_id && $authUserId && (int)$portfolio->user_id === (int)$authUserId) {
                $isOwner = true;
            }
            if ($portfolio->company_id && $authCompanyId && (int)$portfolio->company_id === (int)$authCompanyId) {
                $isOwner = true;
            }
            if (!$isOwner) {
                $this->Flash->error('この投稿にはアクセスできません。');
                return $this->redirect(['action' => 'index']); // index が無いならトップ等に変更
            }
        }

        // ===== ユーザー投稿のときだけフォロー情報を出す =====
        $showFollowUi   = false;
        $followerCount  = 0;
        $followingCount = 0;
        $isFollowing    = false;

        if (!empty($portfolio->user_id)) {
            $showFollowUi = true;
            $userId = (int)$portfolio->user_id;

            $followerCount = $this->Follows->find()
                ->where(['followed_id' => $userId])
                ->count();

            $followingCount = $this->Follows->find()
                ->where(['follower_id' => $userId])
                ->count();

            if ($authUserId !== null && (int)$authUserId !== $userId) {
                $isFollowing = $this->Follows->exists([
                    'follower_id' => (int)$authUserId,
                    'followed_id' => $userId,
                ]);
            }
        }

        // コメント（新しい順）
        $comments = $this->Comments->find()
            ->where(['portfolio_id' => $id])
            ->contain(['Users'])
            ->order(['created' => 'DESC'])
            ->toArray();

        $this->set(compact(
            'portfolio',
            'comments',
            'showFollowUi',
            'followerCount',
            'followingCount',
            'isFollowing'
        ));

        $this->viewBuilder()->setTemplatePath('Portfolios');
        $this->render('view');
    }

    public function edit($id = null)
    {
        $Portfolios = $this->fetchTable('Portfolios');
        $Categories = $this->fetchTable('Categories');

        $portfolio = $Portfolios->get($id, [
            'contain' => ['Categories'],
        ]);

        // 会社オーナーのみ編集可
        $identity  = $this->Authentication->getIdentity();
        $companyId = $identity ? ($identity->get('company_id') ?? $identity->get('id')) : null;

        if (!$companyId || (int)$portfolio->company_id !== (int)$companyId) {
            $this->Flash->error('この投稿を編集する権限がありません。');
            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // サムネ差し替え（任意）
            $thumbnailFile = $this->request->getData('thumbnail_file');
            if ($thumbnailFile && $thumbnailFile->getError() === UPLOAD_ERR_OK) {
                $ext       = strtolower(pathinfo($thumbnailFile->getClientFilename(), PATHINFO_EXTENSION));
                $safeExt   = in_array($ext, ['jpg','jpeg','png','webp'], true) ? $ext : 'jpg';
                $filename  = Text::uuid() . '.' . $safeExt;
                $uploadPath = WWW_ROOT . 'uploads' . DS . $filename;
                $thumbnailFile->moveTo($uploadPath);
                $data['thumbnail'] = '/uploads/' . $filename;
            }

            $portfolio = $Portfolios->patchEntity($portfolio, $data);

            if ($Portfolios->save($portfolio)) {
                try {
                    $this->handlePdfUploads($portfolio);
                } catch (\Throwable $e) {
                    $this->Flash->error('ファイル保存でエラーが発生しました：' . $e->getMessage());
                }

                $this->Flash->success('投稿を更新しました。');
                return $this->redirect(['action' => 'view', $id]);
            }

            $this->Flash->error('投稿の更新に失敗しました。');
        }

        // セレクト用カテゴリ
        $categoryOptions = $Categories->find('list', [
            'keyField'   => 'id',
            'valueField' => 'name',
        ])->toArray();

        // JS 用 slugMap
        $slugMap = $Categories->find()
            ->select(['id', 'slug'])
            ->where(['slug IS NOT' => null])
            ->all()
            ->combine('id', 'slug')
            ->toArray();

        $this->set(compact('portfolio', 'categoryOptions', 'slugMap'));
        $this->viewBuilder()->setTemplatePath('Portfolios');
        $this->render('add'); // add と同じテンプレを再利用
    }

    public function delete($id)
    {
        $portfolio  = $this->Portfolios->get($id);
        $identity   = $this->request->getAttribute('identity');
        $authUserId = $identity ? $identity->get('id') : null;

        if ((int)$portfolio->company_id !== (int)$authUserId) {
            throw new \Cake\Http\Exception\ForbiddenException('この投稿を削除する権限がありません');
        }

        $this->request->allowMethod(['post', 'delete']);

        if ($this->Portfolios->delete($portfolio)) {
            $this->Flash->success('投稿を削除しました');
        } else {
            $this->Flash->error('投稿の削除に失敗しました');
        }

        return $this->redirect(['controller' => 'companies', 'action' => 'view', $authUserId]);
    }

    /**
     * 図面PDF（単一）と補足PDF（複数）を保存し、パスをエンティティに反映して再保存する
     */
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

        // 図面（単一）
        if ($drawing && $drawing->getError() === UPLOAD_ERR_OK) {
            $fname = $this->moveOnePdf($drawing, $baseDir, 'drawing', (int)$portfolio->id);
            $portfolio->drawing_pdf_path = 'files/portfolios/' . $portfolio->id . '/' . $fname;
        }

        // 補足（複数）
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

        // JSON 文字列に（text カラム等を想定）
        if (is_array($portfolio->supplement_pdf_paths)) {
            $portfolio->supplement_pdf_paths = json_encode($portfolio->supplement_pdf_paths, JSON_UNESCAPED_SLASHES);
        }

        $Portfolios = $this->fetchTable('Portfolios');
        if (!$Portfolios->save($portfolio)) {
            throw new \RuntimeException('ファイルパスの保存に失敗しました。');
        }
    }

    /**
     * PDF 1ファイルを検証して保存し、保存したファイル名を返す
     */
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
}
