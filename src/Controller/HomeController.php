<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;

/**
 * Home Controller
 *
 * @method \App\Model\Entity\Home[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class HomeController extends AppController
{
    /**
     * フィルタ設定
     *
     * - index アクションのみ認証不要に設定
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
     * ホームページ表示
     *
     * - 公開ポートフォリオの一覧を取得して表示
     * - キーワード検索 (タイトル部分一致)
     * - 直近の投稿を 18 件まで取得
     * - 各ポートフォリオに「いいね数」と「自分がいいね済みかどうか」を付与
     *
     * @return void
     */
    public function index()
    {
        $this->Portfolios = $this->fetchTable('Portfolios');
        $this->Likes = $this->fetchTable('Likes');

        $identity = $this->request->getAttribute('identity');
        $authId = $identity ? $identity->get('id') : null;

        $keyword = $this->request->getQuery('q');

        $query = $this->Portfolios->find()
            ->contain(['Users'])
            ->where(['is_public' => true])
            ->order(['created' => 'DESC'])
            ->limit(18);

        if (!empty($keyword)) {
            $query->where(['Portfolios.title LIKE' => '%' . $keyword . '%']);
        }

        $portfolios = $query->toArray();

        // いいね数と自分のいいね状態を付与
        foreach ($portfolios as $p) {
            $p->like_count = $this->Likes->find()->where(['portfolio_id' => $p->id])->count();
            $p->liked_by_me = $authId
                ? $this->Likes->exists(['user_id' => $authId, 'portfolio_id' => $p->id])
                : false;
        }

        $this->set(compact('portfolios', 'keyword'));
    }
}
