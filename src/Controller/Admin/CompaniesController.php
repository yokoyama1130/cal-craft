<?php
declare(strict_types=1);

namespace App\Controller\Admin;

class CompaniesController extends AppController
{
    /**
     * 企業一覧を表示する管理画面アクション。
     *
     * - クエリパラメータに応じて検索条件を付与
     *   - verified: 認証状態で絞り込み
     *   - plan: プラン種別で絞り込み
     *   - q: 部分一致検索（企業名・ドメイン）
     * - 更新日の降順で並び替え
     * - ページネーション（1ページ20件）
     *
     * @return void
     */
    public function index()
    {
        $q = $this->request->getQuery();
        $Companies = $this->fetchTable('Companies');

        $query = $Companies->find()->order(['Companies.modified' => 'DESC']);

        if (!empty($q['verified'])) {
            $query->where(['Companies.verified' => (int)$q['verified']]);
        }
        if (!empty($q['plan'])) {
            $query->where(['Companies.plan' => $q['plan']]);
        }
        if (!empty($q['q'])) {
            $kw = '%' . str_replace('%', '\%', $q['q']) . '%';
            $query->where(['OR' => [
                'Companies.name LIKE' => $kw,
                'Companies.domain LIKE' => $kw,
            ]]);
        }

        $this->paginate = ['limit' => 20];
        $companies = $this->paginate($query);
        $this->set(compact('companies', 'q'));
    }

    /**
     * 企業アカウントの認証状態を「Verified」に更新する。
     *
     * - POST リクエストのみ許可
     * - 指定 ID の企業を取得し、verified を 1 に更新
     * - 成功メッセージを表示し、直前のページにリダイレクト
     *
     * @param int $id 企業ID
     * @return \Cake\Http\Response リダイレクトレスポンス
     */
    public function verify($id)
    {
        $this->request->allowMethod(['post']);
        $co = $this->fetchTable('Companies')->get($id);
        $co->verified = 1;
        $this->fetchTable('Companies')->save($co);
        $this->Flash->success('Verifiedにしました。');

        return $this->redirect($this->referer());
    }

    /**
     * 企業アカウントのプランを更新する。
     *
     * - POST リクエストのみ許可
     * - 指定 ID の企業を取得し、plan を更新（free / pro / enterprise）
     * - 成功メッセージを表示し、直前のページにリダイレクト
     *
     * @param int $id 企業ID
     * @param string $plan プラン種別 ('free'|'pro'|'enterprise')
     * @return \Cake\Http\Response リダイレクトレスポンス
     */
    public function plan($id, $plan)
    {
        $this->request->allowMethod(['post']);
        $co = $this->fetchTable('Companies')->get($id);
        $co->plan = $plan; // 'free'|'pro'|'enterprise'
        $this->fetchTable('Companies')->save($co);
        $this->Flash->success('プランを更新しました。');

        return $this->redirect($this->referer());
    }
}
