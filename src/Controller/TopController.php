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
 */
class TopController extends AppController
{
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
     * トランザクション内で一貫したスナップショットを取得し、N+1 を回避するために
     * いいね関連はバッチで集計します。
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

        // 現在の identity を簡単に解決（null / ['user_id' => x] / ['company_id' => x']）
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

                // ポートフォリオID群を抜き出す
                $portfolioIds = array_map(static function ($p) {
                    return (int)$p->id;
                }, $portfolios);

                // いいね数を一括で取得
                $likesCounts = $this->Likes->find()
                    ->select(['portfolio_id', 'cnt' => 'COUNT(*)'])
                    ->where(['portfolio_id IN' => $portfolioIds])
                    ->group('portfolio_id')
                    ->enableHydration(false)
                    ->indexBy('portfolio_id')
                    ->toArray();

                // actor が存在すれば「自分がいいね済みか」を一括取得
                $likedByMe = [];
                if (!empty($actor)) {
                    $conditions = ['portfolio_id IN' => $portfolioIds];
                    // マージして user_id または company_id の条件を追加
                    $conditions = array_merge($conditions, $actor);

                    $rows = $this->Likes->find()
                        ->select(['portfolio_id'])
                        ->where($conditions)
                        ->enableHydration(false)
                        ->toArray();

                    foreach ($rows as $r) {
                        $likedByMe[(int)$r['portfolio_id']] = true;
                    }
                }

                // ポートフォリオごとにプロパティを付与
                foreach ($portfolios as $p) {
                    $pid = (int)$p->id;
                    $p->like_count = isset($likesCounts[$pid]) ? (int)$likesCounts[$pid]['cnt'] : 0;
                    $p->liked_by_me = !empty($likedByMe[$pid]);
                }

                return $portfolios;
            });
        } catch (\Throwable $e) {
            // 例外発生時はログ出力してユーザ向けにメッセージ表示（詳細はログ）
            $this->getLogger()->error('Failed loading portfolios for top page: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            $this->Flash->error(__('トップページの読み込みに失敗しました。時間をおいて再度お試しください。'));

            // 空配列でビューに渡して安全にフォールバック
            $result = [];
        }

        $this->set('portfolios', $result);
    }

    /**
     * Identity から actor 条件を解決する
     *
     * - Users テーブルに存在すれば ['user_id' => id]
     * - Companies テーブルに存在すれば ['company_id' => id]
     * - それ以外は空配列
     *
     * @return array
     */
    private function resolveActorFromIdentity(): array
    {
        $identity = $this->request->getAttribute('identity');
        if (!$identity) {
            return [];
        }

        $id = (int)$identity->get('id');

        // Users に存在するかチェック
        if ($this->Users->exists(['id' => $id])) {
            return ['user_id' => $id];
        }

        // Companies に存在するかチェック
        if ($this->Companies->exists(['id' => $id])) {
            return ['company_id' => $id];
        }

        return [];
    }

    /**
     * ログ出力用のロガーを返す
     *
     * @return \Psr\Log\LoggerInterface
     */
    private function getLogger(): LoggerInterface
    {
        // まずコントローラに $this->log がセットされている場合はそれを使う
        if (property_exists($this, 'log') && $this->log instanceof LoggerInterface) {
            return $this->log;
        }

        // CakePHP の Log ファサードからデフォルトエンジンを取得して返す
        // Log::engine() は通常 LoggerInterface 相当のインスタンスを返します
        return \Cake\Log\Log::engine('default');
    }
}
