<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\ConnectionManager;
use Cake\Event\EventInterface;

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
     *
     * @return void
     */
    public function index(): void
    {
        // テーブルはローカル変数で取得（IDE 警告を減らすため）
        $Portfolios = $this->fetchTable('Portfolios');
        $Likes = $this->fetchTable('Likes');
        $Users = $this->fetchTable('Users');
        $Companies = $this->fetchTable('Companies');

        // actor 条件を解決（user_id または company_id を返す）
        $actor = $this->resolveActorFromIdentity($Users, $Companies);

        $connection = ConnectionManager::get('default');

        // デフォルト結果（例外などで未定義にならないように）
        $result = [];

        try {
            $result = $connection->transactional(function () use ($actor, $Portfolios, $Likes) {
                // 最新の公開ポートフォリオを取得
                $portfolios = $Portfolios->find()
                    ->contain(['Users'])
                    ->where(['is_public' => true])
                    ->order(['created' => 'DESC'])
                    ->limit(10)
                    ->toArray();

                if (empty($portfolios)) {
                    return [];
                }

                // ID 列を抽出してユニーク化
                $portfolioIds = [];
                foreach ($portfolios as $p) {
                    $portfolioIds[] = (int)$p->id;
                }
                $portfolioIds = array_values(array_unique($portfolioIds));

                if (empty($portfolioIds)) {
                    return $portfolios;
                }

                // いいね数を一括取得
                $likesCounts = $Likes->find()
                    ->select(['portfolio_id', 'cnt' => 'COUNT(*)'])
                    ->where(['portfolio_id IN' => $portfolioIds])
                    ->group('portfolio_id')
                    ->enableHydration(false)
                    ->indexBy('portfolio_id')
                    ->toArray();

                // actor があれば自分がいいね済みかを一括取得
                $likedByMe = [];
                if (!empty($actor)) {
                    $conditions = ['portfolio_id IN' => $portfolioIds] + $actor;
                    $rows = $Likes->find()
                        ->select(['portfolio_id'])
                        ->where($conditions)
                        ->enableHydration(false)
                        ->toArray();
                    foreach ($rows as $r) {
                        $likedByMe[(int)$r['portfolio_id']] = true;
                    }
                }

                // 各ポートフォリオに付加情報をセット
                foreach ($portfolios as $p) {
                    $pid = (int)$p->id;
                    $p->like_count = isset($likesCounts[$pid]) ? (int)$likesCounts[$pid]['cnt'] : 0;
                    $p->liked_by_me = !empty($likedByMe[$pid]);
                }

                return $portfolios;
            });
        } catch (\Throwable $e) {
            // 詳細はログに残す（安全のため詳細はユーザに表示しない）
            \Cake\Log\Log::error('TopController::index failed: ' . $e->getMessage(), ['exception' => $e]);
            $this->Flash->error(__('トップページの読み込みに失敗しました。時間をおいて再度お試しください。'));
            $result = [];
        }

        // ビューに渡す前に最低限のサニタイズ（例: ユーザの機密を除去）
        foreach ($result as $p) {
            if (isset($p->user) && is_object($p->user)) {
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
     * - Users/Companies テーブルを引数で受け取りテスト可能にする
     *
     * @param \App\Model\Table\UsersTable $Users UsersTable インスタンス
     * @param \App\Model\Table\CompaniesTable $Companies CompaniesTable インスタンス
     * @return array ['user_id' => int] | ['company_id' => int] | []
     */
    private function resolveActorFromIdentity(\App\Model\Table\UsersTable $Users, \App\Model\Table\CompaniesTable $Companies): array
    {
        $identity = $this->request->getAttribute('identity');
        if (!$identity) {
            return [];
        }

        // Identity の識別子を安全に取得
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

        // identity に type/role 情報があればそれを優先（DB 問い合わせを避ける）
        $type = null;
        if (method_exists($identity, 'get')) {
            $type = $identity->get('type');
            if ($type === null) {
                $type = $identity->get('role');
            }
        }

        if ($type === 'user') {
            return ['user_id' => $id];
        }
        if ($type === 'company' || $type === 'employer') {
            return ['company_id' => $id];
        }

        // フォールバック：DB に存在するかを確認（失敗しても安全にフォールバック）
        try {
            if ($Users->exists(['id' => $id])) {
                return ['user_id' => $id];
            }
            if ($Companies->exists(['id' => $id])) {
                return ['company_id' => $id];
            }
        } catch (\Throwable $e) {
            \Cake\Log\Log::warning('resolveActorFromIdentity DB check failed: ' . $e->getMessage(), ['exception' => $e]);
        }

        return [];
    }
}
