<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Utility\Text;
use Psr\Http\Message\UploadedFileInterface;

class PortfoliosController extends AppController
{
    /**
     * beforeFilter
     *
     * - 認証チェック前の処理を定義
     * - `index`, `view`, `search` アクションは未ログインでもアクセス可能に設定
     *
     * @param \Cake\Event\EventInterface $event イベントオブジェクト
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        // 公開一覧・公開詳細・検索は未ログインでもOK
        $this->Authentication->addUnauthenticatedActions(['index', 'view', 'search']);
    }

    /**
     * ポートフォリオ一覧を表示
     *
     * - 公開状態（is_public = true）のポートフォリオを新しい順に最大10件取得
     * - 各ポートフォリオに対して以下の情報を付与する:
     *   - like_count: そのポートフォリオに付いた「いいね」の総数
     *   - liked_by_me: ログイン中ユーザーが「いいね」しているかどうか
     * - ビューに `portfolios` 変数をセットして表示用に渡す
     *
     * @return void
     */
    public function index()
    {
        $this->Likes = $this->fetchTable('Likes');

        $identity = $this->request->getAttribute('identity');
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

    /**
     * ポートフォリオ詳細を表示
     *
     * - 指定された ID のポートフォリオを取得し、関連情報（Users, Companies, Categories, Comments）を含めて表示する
     * - 非公開ポートフォリオは、投稿者本人のみが閲覧可能
     * - ユーザー投稿の場合はフォローUIを表示し、フォロワー数・フォロー数・ログイン中ユーザーがフォロー中かどうかを判定
     * - コメント一覧を取得し、関連するユーザーや企業情報も含めてビューに渡す
     * - ビューに渡す変数:
     *   - portfolio: ポートフォリオ本体
     *   - comments: コメント一覧（昇順）
     *   - showFollowUi: フォローUIを表示するかどうか（bool）
     *   - followerCount: フォロワー数
     *   - followingCount: フォロー数
     *   - isFollowing: 現在ログイン中ユーザーが対象をフォローしているか
     *   - currentActor: ログイン中のユーザーまたは企業情報
     *
     * @param int|null $id ポートフォリオID
     * @return \Cake\Http\Response|null レンダリングまたはリダイレクト
     */
    public function view($id = null)
    {
        $this->Follows = $this->fetchTable('Follows');
        $this->Comments = $this->fetchTable('Comments');

        // 会社情報もビューファイルで使うなら 'Companies' を含める
        $portfolio = $this->Portfolios->get($id, [
            'contain' => ['Users', 'Companies', 'Categories', 'Comments' => ['Users']],
        ]);

        // 認証ID（未ログインなら null）
        $identity = $this->request->getAttribute('identity');
        $authUserId = $identity ? $identity->get('id') : null;

        // 非公開は本人のみ（ユーザー投稿想定）
        if (!$portfolio->is_public) {
            if ($authUserId === null || (int)$portfolio->user_id !== (int)$authUserId) {
                $this->Flash->error('この投稿にはアクセスできません。');

                return $this->redirect(['action' => 'index']);
            }
        }

        // ===== フォローUIはユーザー投稿のときだけ =====
        $showFollowUi = false;
        $followerCount = 0;
        $followingCount = 0;
        $isFollowing = false;

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
        $currentActor = $this->getActor();
        $this->set(compact('comments', 'currentActor'));

        $this->set(compact(
            'portfolio',
            'comments',
            'showFollowUi',
            'followerCount',
            'followingCount',
            'isFollowing'
        ));
    }

    /**
     * ポートフォリオ新規作成
     *
     * - POST リクエストで送信されたデータを基に新しいポートフォリオを作成
     * - ログインしているユーザーを投稿者（user_id）として設定（company_id は null）
     * - サムネイル画像がアップロードされている場合は `/uploads/` に保存し、パスを `thumbnail` フィールドに設定
     * - PDF など追加ファイルのアップロード処理は `handlePdfUploads()` で処理（例外時はエラーメッセージを表示）
     * - 成功時は該当ポートフォリオの詳細ページへリダイレクト、失敗時はエラーメッセージを表示
     * - ビューへ渡す変数:
     *   - portfolio: 新規作成中のポートフォリオエンティティ
     *   - categories: 投稿フォーム用カテゴリ一覧（id, name, slug）
     *
     * @return \Cake\Http\Response|null レンダリングまたはリダイレクト
     */
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
                $ext = strtolower(pathinfo($thumbnailFile->getClientFilename(), PATHINFO_EXTENSION));
                $safeExt = in_array($ext, ['jpg','jpeg','png','webp'], true) ? $ext : 'jpg';
                $filename = Text::uuid() . '.' . $safeExt;
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

    /**
     * ポートフォリオ編集
     *
     * - 指定された ID のポートフォリオを取得し、投稿者本人のみが編集可能
     * - ログインしていない場合、または他人の投稿の場合はエラーを表示して詳細ページへリダイレクト
     * - PATCH/POST/PUT リクエストで送信されたデータを反映し、更新処理を実行
     * - サムネイル画像がアップロードされた場合は `/uploads/` に保存し、`thumbnail` に反映
     * - 更新成功時は PDF など追加ファイルの処理を行い、詳細ページにリダイレクト
     * - 更新失敗時はエラーメッセージを表示してフォームを再描画
     * - ビューは add フォームを再利用
     *
     * @param int|null $id 編集対象のポートフォリオID
     * @return \Cake\Http\Response|null 成功時はリダイレクト、失敗時はフォームを再表示
     */
    public function edit($id = null)
    {
        $portfolio = $this->Portfolios->get($id, [
            'contain' => ['Categories'],
        ]);

        // 自分の投稿しか編集できない
        $identity = $this->request->getAttribute('identity');
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
                $ext = strtolower(pathinfo($thumbnailFile->getClientFilename(), PATHINFO_EXTENSION));
                $safeExt = in_array($ext, ['jpg','jpeg','png','webp'], true) ? $ext : 'jpg';
                $filename = Text::uuid() . '.' . $safeExt;
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

    /**
     * ポートフォリオ削除
     *
     * - 指定された ID のポートフォリオを取得し、投稿者本人のみ削除可能
     * - 権限がない場合は ForbiddenException をスロー
     * - POST または DELETE リクエストのみ許可
     * - 成功時はユーザープロフィールにリダイレクト
     *
     * @param int $id 削除対象のポートフォリオID
     * @return \Cake\Http\Response リダイレクトレスポンス
     * @throws \Cake\Http\Exception\ForbiddenException 投稿者以外による削除時
     */
    public function delete($id)
    {
        $portfolio = $this->Portfolios->get($id);
        $identity = $this->request->getAttribute('identity');
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

    /**
     * 公開／非公開の切り替え
     *
     * - 投稿者本人のみ操作可能
     * - is_public フラグをトグルして保存
     * - 実行後はユーザープロフィールへリダイレクト
     *
     * @param int $id 対象ポートフォリオID
     * @return \Cake\Http\Response リダイレクトレスポンス
     * @throws \Cake\Http\Exception\ForbiddenException 投稿者以外による操作時
     */
    public function togglePublic($id)
    {
        $portfolio = $this->Portfolios->get($id);
        $identity = $this->request->getAttribute('identity');
        $authUserId = $identity ? $identity->get('id') : null;

        if ((int)$portfolio->user_id !== (int)$authUserId) {
            throw new \Cake\Http\Exception\ForbiddenException();
        }

        $portfolio->is_public = !$portfolio->is_public;
        $this->Portfolios->save($portfolio);

        return $this->redirect(['controller' => 'Users', 'action' => 'profile']);
    }

    /**
     * ポートフォリオ検索
     *
     * - GET メソッドのみ許可
     * - クエリパラメータ `q` を利用してタイトル／説明を部分一致検索
     * - 常に公開設定（is_public = true）の投稿のみ対象
     * - 検索結果を新しい順に表示し、`index` テンプレートを使用
     *
     * @return void
     */
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
                ],
            ]);
        }

        $portfolios = $query->order(['Portfolios.created' => 'DESC'])->toArray();

        $this->set(compact('portfolios', 'keyword'));
        $this->render('index');
    }

    /**
     * 単一PDFファイルを保存
     *
     * - PDF 拡張子と MIME タイプをチェック
     * - サイズ上限は 20MB
     * - 保存先は `files/portfolios/{portfolioId}/` 配下
     * - 安全な一意ファイル名を生成して保存
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file アップロードされたファイル
     * @param string $baseDir 保存先ディレクトリ
     * @param string $kind 種別（drawing|supplement など）
     * @param int $pid ポートフォリオID
     * @return string 保存されたファイル名
     * @throws \RuntimeException PDF 以外のファイルや制約違反時
     */
    private function moveOnePdf(UploadedFileInterface $file, string $baseDir, string $kind, int $pid): string
    {
        $ext = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            throw new \RuntimeException('PDFのみアップロードできます。');
        }

        $stream = $file->getStream();
        $contents = $stream->getContents();
        $stream->rewind();

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($contents);
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

    /**
     * PDF ファイルのアップロード処理
     *
     * - 図面 PDF（drawing_pdf）と補足資料 PDF（supplement_pdfs[]）を処理
     * - PDF のバリデーション（拡張子・MIME・サイズ）を実行
     * - 保存先ディレクトリが存在しない場合は自動生成
     * - 複数補足資料は既存のものに追記する形で保存
     * - 保存パスは JSON 化してエンティティに格納
     * - Portfolios テーブルに保存できなければ RuntimeException をスロー
     *
     * @param \App\Model\Entity\Portfolio $portfolio 対象のポートフォリオ
     * @return void
     * @throws \RuntimeException ファイル保存や DB 更新失敗時
     */
    private function handlePdfUploads(\App\Model\Entity\Portfolio $portfolio): void
    {
        $req = $this->request;
        $drawing = $req->getData('drawing_pdf');
        $supps = (array)$req->getData('supplement_pdfs');

        if (!$drawing && empty(array_filter($supps))) {
            return;
        }

        $baseDir = WWW_ROOT . 'files' . DS . 'portfolios' . DS . $portfolio->id . DS;
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

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
            $current = $portfolio->supplement_pdf_paths;
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
