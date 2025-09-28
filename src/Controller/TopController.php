<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;

class TopController extends AppController
{
    /**
     * beforeFilter
     *
     * 各アクション実行前のフィルタ処理。
     * TopController では index アクションだけ未ログイン状態でもアクセス可能に設定する。
     *
     * @param \Cake\Event\EventInterface $event イベントオブジェクト
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        // indexアクションだけログイン不要にする
        $this->Authentication->addUnauthenticatedActions(['index']);
    }

    /**
     * index
     *
     * 公開されている最新のポートフォリオを最大10件まで取得し、トップページに表示する。
     * 各ポートフォリオについて「いいね数」と「自分がいいね済みかどうか」を付与する。
     * ユーザー/会社アカウントいずれのログインにも対応。
     *
     * @return void
     */
    public function index()
    {
        $this->Likes = $this->fetchTable('Likes');
        $this->Portfolios = $this->fetchTable('Portfolios');

        $identity = $this->request->getAttribute('identity');
        $actor = [];
        if ($identity) {
            $id = (int)$identity->get('id');
            // Users or Companies?
            $this->Users = $this->fetchTable('Users');
            if ($this->Users->exists(['id' => $id])) {
                $actor = ['user_id' => $id];
            } else {
                $this->Companies = $this->fetchTable('Companies');
                if ($this->Companies->exists(['id' => $id])) {
                    $actor = ['company_id' => $id];
                }
            }
        }

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

            $p->liked_by_me = false;
            if ($actor) {
                $p->liked_by_me = $this->Likes->exists(array_merge(['portfolio_id' => $p->id], $actor));
            }
        }

        $this->set(compact('portfolios'));
    }
}
