<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Log\Log;

class LikesController extends AppController
{
    /**
     * コントローラーの初期化処理
     *
     * - 親クラスの initialize() を呼び出し、共通初期化を継承。
     * - Authentication コンポーネントをロードし、認証機能を有効化。
     * - Likes テーブルを明示的にロードして、コントローラー内で利用可能にする。
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Authentication');
        $this->Likes = $this->fetchTable('Likes'); // Likes テーブルを明示的にロード
        // $this->loadModel('Portfolios'); ← 必要なければ消してOK
    }

    /**
     * いいね追加（トグル機能付き）
     *
     * - POST リクエストのみ受け付け
     * - すでに「user_id × portfolio_id」のレコードがある場合は削除（アンいいね）
     * - 存在しない場合は新規作成
     * - 保存成功時には、対象ポートフォリオの投稿者に通知を送信
     *   （ただし、自分自身へのいいねは通知しない）
     * - 成功・失敗いずれの場合も元のページへリダイレクト
     *
     * @return \Cake\Http\Response リダイレクトレスポンス
     */
    public function add()
    {
        $this->request->allowMethod(['post']);

        $user = $this->Authentication->getIdentity();
        $portfolioId = $this->request->getData('portfolio_id');

        // すでに同じ user_id × portfolio_id のレコードがあるか確認
        $existingLike = $this->Likes->find()
            ->where([
                'user_id' => $user->get('id'),
                'portfolio_id' => $portfolioId,
            ])
            ->first();

        if ($existingLike) {
            // 👇 すでにいいねしてる場合は削除（トグル）
            $this->Likes->delete($existingLike);

            return $this->redirect($this->referer());
        }

        // いいねしてない場合は追加
        $like = $this->Likes->newEmptyEntity();
        $like = $this->Likes->patchEntity($like, [
            'user_id' => $user->get('id'),
            'portfolio_id' => $portfolioId,
        ]);

        if ($this->Likes->save($like)) {
            return $this->redirect($this->referer());
        }

        $this->Flash->error('いいねできませんでした');

        $portfolio = $this->Likes->Portfolios->get($portfolioId);

        // いいね成功後に通知
        // ここの警告うざいなー
        if ($this->Likes->save($like)) {
            // 通知を作成（自分自身へのいいねは除外）
            if ($user->get('id') !== $portfolio->user_id) {
                $this->Notifications = $this->fetchTable('Notifications');
                $notification = $this->Notifications->newEmptyEntity();
                $notification->user_id = $portfolio->user_id; // 通知を受け取る人
                $notification->sender_id = $user->get('id'); // 通知を送った人
                $notification->portfolio_id = $portfolioId;
                $notification->type = 'like';
                $this->Notifications->save($notification);
            }

            return $this->redirect($this->referer());
        }

        $this->Flash->error('いいねできませんでした');

        return $this->redirect($this->referer());
    }

    /**
     * 現在のログイン主体（ユーザー or 企業）を解決するヘルパー
     *
     * - 認証情報（identity）から ID を取得
     * - Users テーブルに存在すれば ['user_id' => ID] を返す
     * - Companies テーブルに存在すれば ['company_id' => ID] を返す
     * - どちらにも存在しない、または未ログインの場合は空配列を返す
     *
     * @return array ユーザーID または 企業ID を含む配列、または空配列
     */
    private function resolveActor(): array
    {
        $identity = $this->request->getAttribute('identity');
        if (!$identity) {
            return [];
        }
        $id = (int)$identity->get('id');

        // Users/Companies どちらのIDか判定（両方無ければ空配列）
        $this->Users = $this->fetchTable('Users');
        if ($this->Users->exists(['id' => $id])) {
            return ['user_id' => $id];
        }
        $this->Companies = $this->fetchTable('Companies');
        if ($this->Companies->exists(['id' => $id])) {
            return ['company_id' => $id];
        }

        return [];
    }

    /**
     * いいねトグル（Ajax用）
     *
     * - POST リクエストのみ受け付け
     * - 現在のログイン主体（ユーザー or 企業）を resolveActor() で判定
     * - すでに同じ組み合わせ（user_id / company_id × portfolio_id）が存在する場合は削除（アンいいね）
     * - 存在しない場合は新規作成し、対象ユーザーに通知を送信（※自分自身の投稿は通知対象外）
     * - 成功時は JSON レスポンスとして { success, liked, likeCount } を返却
     * - エラー時は HTTP ステータスコード（401, 400, 422, 500 など）と JSON エラー内容を返却
     *
     * @return \Cake\Http\Response JSON レスポンス
     */
    public function toggle()
    {
        $this->request->allowMethod(['post']);
        $this->Likes = $this->fetchTable('Likes');
        $this->Notifications = $this->fetchTable('Notifications');
        $this->Portfolios = $this->fetchTable('Portfolios');

        try {
            $actor = $this->resolveActor();
            if (!$actor) {
                // 未ログイン or IDの突合に失敗
                return $this->response->withType('application/json')
                    ->withStatus(401)
                    ->withStringBody(json_encode([
                        'success' => false,
                        'error' => 'ログインが必要です',
                    ]));
            }

            $portfolioId = (int)$this->request->getData('portfolio_id');
            if (!$portfolioId) {
                return $this->response->withType('application/json')
                    ->withStatus(400)
                    ->withStringBody(json_encode([
                        'success' => false,
                        'error' => 'portfolio_id が不正です',
                    ]));
            }

            $conditions = array_merge(['portfolio_id' => $portfolioId], $actor);
            $existing = $this->Likes->find()->where($conditions)->first();

            $liked = false;
            if ($existing) {
                $this->Likes->delete($existing);
            } else {
                // 会社アカウントでも通るように actor をそのまま差し込む
                $like = $this->Likes->newEntity(array_merge(['portfolio_id' => $portfolioId], $actor));
                if (!$this->Likes->save($like)) {
                    // バリデーションエラーなど
                    return $this->response->withType('application/json')
                        ->withStatus(422)
                        ->withStringBody(json_encode([
                            'success' => false,
                            'error' => '保存に失敗しました',
                            'errors' => $like->getErrors(),
                        ]));
                }
                $liked = true;

                // 通知（自分の投稿へのいいねは通知しない）
                $portfolio = $this->Portfolios->get($portfolioId);
                // Users投稿のみ通知対象にしている想定。必要なら company向けにも拡張してOK
                if (($actor['user_id'] ?? null) !== $portfolio->user_id) {
                    $notification = $this->Notifications->newEntity([
                        'user_id' => $portfolio->user_id,
                        'sender_id' => ($actor['user_id'] ?? $actor['company_id']),
                        'portfolio_id' => $portfolio->id,
                        'type' => 'like',
                        'is_read' => false,
                    ]);
                    $this->Notifications->save($notification);
                }
            }

            $likeCount = $this->Likes->find()->where(['portfolio_id' => $portfolioId])->count();

            return $this->response->withType('application/json')->withStringBody(json_encode([
                'success' => true,
                'liked' => $liked,
                'likeCount' => $likeCount,
            ]));
        } catch (\Throwable $e) {
            // サーバ側で必ずログに残す
            Log::error('[likes/toggle] ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            // フロントにはJSONで返す（HTMLエラーページを返さない）
            return $this->response->withType('application/json')
                ->withStatus(500)
                ->withStringBody(json_encode([
                    'success' => false,
                    'error' => 'サーバーエラーが発生しました',
                ]));
        }
    }

    /**
     * お気に入り一覧（自分が「いいね」したポートフォリオを表示）
     *
     * - 現在のログイン主体（ユーザー or 企業）を resolveActor() で判定
     * - 未ログインの場合はエラーメッセージを出して /login にリダイレクト
     * - 自分が「いいね」したポートフォリオIDを取得し、
     *   - 公開中（is_public = true）のみ一覧表示
     *   - Users を関連付けて取得
     *   - 作成日の新しい順に並び替え
     * - 各ポートフォリオに liked_by_me（常に true）と like_count を付与
     * - ビューに `portfolios` を渡して表示
     *
     * @return \Cake\Http\Response|null レスポンスまたはリダイレクト
     */
    public function favorites()
    {
        $this->Likes = $this->fetchTable('Likes');
        $this->Users = $this->fetchTable('Users');
        $this->Portfolios = $this->fetchTable('Portfolios');

        // ユーザー/会社どちらのIDか判定（さっき作ったやつを再利用）
        $actor = $this->resolveActor();
        if (!$actor) {
            // ログイン必須にする場合はリダイレクトでもOK
            $this->Flash->error('お気に入りを表示するにはログインが必要です。');

            return $this->redirect('/login'); // 仕様に合わせて
        }

        // 自分が「いいね」したポートフォリオID（ユーザー/会社どちらかで絞り込み）
        $likedPortfolioIds = $this->Likes->find()
            ->select(['portfolio_id'])
            ->where($actor) // ← ここがポイント（user_id または company_id）
            ->andWhere(['portfolio_id IS NOT' => null])
            ->enableHydration(false)
            ->extract('portfolio_id')
            ->toArray();

        $portfolios = [];
        if ($likedPortfolioIds) {
            $portfolios = $this->Portfolios->find()
                ->where(['Portfolios.id IN' => $likedPortfolioIds, 'Portfolios.is_public' => true])
                ->contain(['Users'])
                ->order(['Portfolios.created' => 'DESC'])
                ->toArray();

            // liked_by_me / like_count を付与
            foreach ($portfolios as $p) {
                $p->liked_by_me = true; // 自分の「お気に入り」一覧なので常に true
                $p->like_count = $this->Likes->find()
                    ->where(['portfolio_id' => $p->id])
                    ->count();
            }
        }

        $this->set(compact('portfolios'));
    }
}
