<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\ConnectionManager;
use Cake\Event\EventInterface;
use Psr\Log\LoggerInterface;

/**
 * TopController
 *
 * トップページ（公開ポートフォリオ一覧）の表示を担当します。
 *
 * @property \App\Model\Table\PortfoliosTable $Portfolios
 * @property \App\Model\Table\LikesTable $Likes
 * @property \App\Model\Table\UsersTable $Users
 * @property \App\Model\Table\CompaniesTable $Companies
 */
class TopController extends AppController
{
    private LoggerInterface $logger;

    /**
     * 初期化処理（ロガー注入）
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        // デフォルトのログエンジンを取得してプロパティに保持します
        // Log::engine() は環境に依存しますが、通常は PSR-3 準拠のロガーを返します
        $this->logger = \Cake\Log\Log::engine('default');
    }

    /**
     * beforeFilter
     *
     * index を未認証で許可する。
     *
     * @param \Cake\Event\EventInterface $event イベントオブジェクト
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // indexアクションだけログイン不要にする
        $this->Authentication->addUnauthenticatedActions(['index']);
    }

    /**
     * index
     *
     * 公開されている最新のポートフォリオを最大10件まで取得して表示する。
     * いいね数と自分がいいね済みかを付与する。
     *
     * - トランザクションで読み取りを囲って一貫したスナップショットを得る（DB設定依存）
     * - N+1 を回避するために likes 関連は一括で集計する
     * - identity の情報があれば DB への余分な問い合わせを避ける
     *
     * @return void
     */
    public function index(): void
    {
        // 必要なテーブルを事前に取得
        $this->Portfolios = $this->fetchTable('Portfolios');
        $this->Likes = $this->fetchTable('Likes');
        $this->Users = $this->fetchTable('Users');
        $this->Companies = $this->fetchTable('Companies');

        // 現在の identity を解決（空配列 / ['user_id' => x] / ['company_id' => x]）
        $actor = $this->resolveActorFromIdentity();

        // DB 接続を取得してトランザクションで読み取りを囲む（読み取り一貫性確保）
        $connection = ConnectionManager::get('default');

        try {
            $result = $connection->transactional(function () use ($actor) {
                // まずポートフォリオを取得（最新10件）
                $portfolios = $this->Portfolios->find()
                    ->contain(['Users'])
                    ->where(['is_public' => true])
                    ->order(['created' => 'DESC'])
                    ->limit(10)
                    ->toArray();

                if (empty($portfolios)) {
                    return [];
                }

                // ポートフォリオID群を抽出（ユニーク化して安全に cast）
                $portfolioIds = [];
                foreach ($portfolios as $p) {
                    $portfolioIds[] = (int)$p->id;
                }
                $portfolioIds = array_values(array_unique($portfolioIds));

                if (empty($portfolioIds)) {
                    return $portfolios;
                }

                // いいね数を一括で取得（戻りは portfolio_id => ['portfolio_id'=>..., 'cnt'=>... ]）
                $likesCounts = $this->Likes->find()
                    ->select(['portfolio_id', 'cnt' => 'COUNT(*)'])
                    ->where(function ($exp, $q) use ($portfolioIds) {
                        // 明示的に IN を使い、パラメタライズさせる
                        return $exp->in('portfolio_id', $portfolioIds);
                    })
                    ->group('portfolio_id')
                    ->enableHydration(false)
                    ->indexBy('portfolio_id')
                    ->toArray();

                // actor が存在すれば「自分がいいね済みか」を一括取得
                $likedByMe = [];
                if (!empty($actor)) {
                    $conditions = ['portfolio_id IN' => $portfolioIds];
                    $conditions = array_merge($conditions, $actor);

                    $rows = $this->Likes->find()
                        ->select(['portfolio_id'])
                        ->where(function ($exp) use ($conditions) {
                            $in = $conditions['portfolio_id IN'];
                            $expr = $exp->in('portfolio_id', $in);
                            // user_id or company_id は top-level 条件として追加
                            if (isset($conditions['user_id'])) {
                                $expr = $expr->eq('user_id', $conditions['user_id']);
                            }
                            if (isset($conditions['company_id'])) {
                                $expr = $expr->eq('company_id', $conditions['company_id']);
                            }

                            return $expr;
                        })
                        ->enableHydration(false)
                        ->toArray();

                    foreach ($rows as $r) {
                        $likedByMe[(int)$r['portfolio_id']] = true;
                    }
                }

                // ポートフォリオごとにプロパティを付与（ビュー層での表示値を加工）
                foreach ($portfolios as $p) {
                    $pid = (int)$p->id;
                    $p->like_count = isset($likesCounts[$pid]) ? (int)$likesCounts[$pid]['cnt'] : 0;
                    $p->liked_by_me = !empty($likedByMe[$pid]);
                    // 表示用に不要な内部情報を隠すなどあればここで加工（例: $p->user->email を削除）
                }

                return $portfolios;
            });
        } catch (\Throwable $e) {
            // 例外発生時はログに詳細を残し、ユーザ向けには汎用メッセージを表示する（詳細はログで追う）
            $this->logger->error('TopController::index failed while loading portfolios', [
                'exception' => $e,
            ]);
            $this->Flash->error(__('トップページの読み込みに失敗しました。時間をおいて再度お試しください。'));

            // 空配列でビューに渡して安全にフォールバック
            $result = [];
        }

        // ビューに渡す前に最小限のサニタイズ（必要に応じて拡張）
        foreach ($result as $p) {
            if (isset($p->user) && is_object($p->user)) {
                // ユーザーの機密情報をビューに渡さない
                if (isset($p->user->password)) {
                    unset($p->user->password);
                }
                if (isset($p->user->auth_token)) {
                    unset($p->user->auth_token);
                }
            }
        }

        $this->set('portfolios', $result);
    }

    /**
     * Identity から actor 条件を解決する
     *
     * - identity に type (e.g. 'user'|'company') があれば DB 問い合わせを避ける
     * - それが無ければ Users/Companies テーブルの存在チェックで判別する
     *
     * @return array ['user_id' => int] | ['company_id' => int] | []
     */
    private function resolveActorFromIdentity(): array
    {
        $identity = $this->request->getAttribute('identity');
        if (!$identity) {
            return [];
        }

        // Identity オブジェクトの識別子取得を優先
        $identifier = null;
        if (method_exists($identity, 'getIdentifier')) {
            $identifier = $identity->getIdentifier();
        } elseif ($identity->get('id') !== null) {
            $identifier = $identity->get('id');
        }

        if ($identifier === null) {
            return [];
        }

        $id = (int)$identifier;

        // identity に type/role が含まれている場合はそれを利用して DB 問い合わせを避ける
        $type = $identity->get('type');
        if ($type === null) {
            $type = $identity->get('role');
            if ($type === null) {
                $type = null;
            }
        }

        if ($type === 'user') {
            return ['user_id' => $id];
        }
        if ($type === 'company' || $type === 'employer') {
            return ['company_id' => $id];
        }

        // フォールバック：Users / Companies の存在チェック（最小限の追加クエリ）
        try {
            if ($this->Users->exists(['id' => $id])) {
                return ['user_id' => $id];
            }
            if ($this->Companies->exists(['id' => $id])) {
                return ['company_id' => $id];
            }
        } catch (\Throwable $e) {
            // DB 問い合わせで失敗した場合はログだけに留める（機能性を壊さない）
            $this->logger->warning('resolveActorFromIdentity DB check failed', ['exception' => $e]);
        }

        return [];
    }
}
