<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\Log\Log;

class TopController extends AppController
{
    /**
     * コントローラ初期化。
     * - `Portfolios` と `Likes` テーブルを読み込み。
     * - JSON ビュークラスを設定。
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Portfolios = $this->fetchTable('Portfolios');
        $this->Likes = $this->fetchTable('Likes');
        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * 各アクション実行前のフィルタ。
     * - Authentication が有効な場合、`index` アクションを未認証でも許可。
     *
     * @param \Cake\Event\EventInterface $event イベント
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        // トップは未ログインでもOK
        if ($this->components()->has('Authentication')) {
            $this->Authentication->allowUnauthenticated(['index']);
        }
    }

    /**
     * GET /api/portfolios/index.json
     * 公開ポートフォリオ一覧を取得（最新10件）。
     * - 認証済みなら自身の like 状態（liked_by_me）も付与。
     * - サムネイルや like_count を含む簡易情報を返却。
     * - 失敗時は 500。
     *
     * @return \Cake\Http\Response|null|void JSON をシリアライズして返却
     */
    public function index()
    {
        $this->request->allowMethod(['get']);

        try {
            // ログイン中のアクター（ユーザー or 会社）を特定（無ければ空配列）
            $identity = $this->request->getAttribute('identity');
            $actor = [];
            if ($identity) {
                $id = (int)$identity->get('id');

                // Users or Companies どちらかに存在するか調べる
                try {
                    $Users = $this->fetchTable('Users');
                    if ($Users->exists(['id' => $id])) {
                        $actor = ['user_id' => $id];
                    } else {
                        $Companies = $this->fetchTable('Companies');
                        if ($Companies->exists(['id' => $id])) {
                            $actor = ['company_id' => $id];
                        }
                    }
                } catch (\Throwable $e) {
                    // 片方のテーブルが未作成でも落ちないように握りつぶす
                    Log::warning('Actor detect warning: ' . $e->getMessage());
                }
            }

            $rows = $this->Portfolios->find()
                ->contain(['Users']) // 必要なら Companies も
                ->where(['Portfolios.is_public' => true])
                ->order(['Portfolios.created' => 'DESC'])
                ->limit(10)
                ->toArray();

            $out = [];
            foreach ($rows as $p) {
                // like_count
                $likeCount = $this->Likes->find()
                    ->where(['portfolio_id' => $p->id])
                    ->count();

                // liked_by_me
                $likedByMe = false;
                if ($actor) {
                    $likedByMe = $this->Likes->exists(array_merge(
                        ['portfolio_id' => $p->id],
                        $actor
                    ));
                }

                // サムネは webroot/uploads が正。/img/uploads で保存されているものは補正。
                $thumb = (string)($p->thumbnail ?? '');
                if (strpos($thumb, '/img/uploads/') === 0) {
                    $thumb = preg_replace('#^/img/uploads/#', '/uploads/', $thumb);
                }

                $out[] = [
                    'id' => (int)$p->id,
                    'title' => (string)($p->title ?? ''),
                    'description' => (string)($p->description ?? ''),
                    'thumbnail' => $thumb,
                    'like_count' => (int)$likeCount,
                    'liked_by_me' => (bool)$likedByMe,
                    'user' => $p->user ? [
                        'id' => (int)$p->user->id,
                        'name' => (string)($p->user->name ?? 'User'),
                        // Users.icon_url カラムが無い構成でも落とさない
                        // 'icon_url' => (string)($p->user->icon_url ?? ''),
                    ] : null,
                ];
            }

            $this->set(['success' => true, 'portfolios' => $out]);
            $this->viewBuilder()->setOption('serialize', ['success', 'portfolios']);
        } catch (\Throwable $e) {
            $this->response = $this->response->withStatus(500);
            $this->set(['success' => false, 'message' => $e->getMessage()]);
            $this->viewBuilder()->setOption('serialize', ['success', 'message']);
        }
    }
}
