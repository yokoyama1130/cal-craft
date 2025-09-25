<?php
declare(strict_types=1);

namespace App\Controller\Admin;

class PortfoliosController extends AppController
{
    /**
     * ポートフォリオ一覧を表示する管理画面アクション。
     *
     * - クエリパラメータに応じて絞り込み・検索を実行
     *   - q: タイトル・説明文の部分一致検索
     *   - visibility: 公開 / 非公開の絞り込み
     *   - owner: user → ユーザー所有, company → 企業所有
     * - Users, Companies を contain して取得
     * - 作成日の降順で並べ替え
     * - ページネーション（1ページ20件）
     *
     * @return void
     */
    public function index()
    {
        $q = $this->request->getQueryParams();
        $Portfolios = $this->fetchTable('Portfolios');

        $query = $Portfolios->find()
            ->contain(['Users','Companies'])
            ->order(['Portfolios.created' => 'DESC']);

        if (!empty($q['q'])) {
            $kw = '%' . str_replace('%', '\%', $q['q']) . '%';
            $query->where([
                'OR' => [
                    'Portfolios.title LIKE' => $kw,
                    'Portfolios.description LIKE' => $kw,
                ],
            ]);
        }
        if ($q['visibility'] ?? '' !== '') {
            $query->where(['Portfolios.is_public' => (int)$q['visibility']]);
        }
        if ($q['owner'] ?? '' === 'user') {
            $query->where(['Portfolios.user_id IS NOT' => null]);
        } elseif (($q['owner'] ?? '') === 'company') {
            $query->where(['Portfolios.company_id IS NOT' => null]);
        }

        $this->paginate = ['limit' => 20];
        $portfolios = $this->paginate($query);
        $this->set(compact('portfolios', 'q'));
    }

    /**
     * ポートフォリオの公開状態をトグル（ON/OFF 切り替え）する。
     *
     * - POST リクエストのみ許可
     * - 指定 ID のポートフォリオを取得
     * - is_public を反転させて保存
     * - 成功メッセージを表示し、直前のページにリダイレクト
     *
     * @param int $id ポートフォリオID
     * @return \Cake\Http\Response リダイレクトレスポンス
     */
    public function toggle($id)
    {
        $this->request->allowMethod(['post']);
        $pf = $this->fetchTable('Portfolios')->get($id);
        $pf->is_public = (int)!$pf->is_public;
        $this->fetchTable('Portfolios')->save($pf);
        $this->Flash->success('公開状態を切り替えました。');

        return $this->redirect($this->referer());
    }

    /**
     * ポートフォリオ削除処理（管理者用）。
     *
     * - POST または DELETE リクエストのみ許可
     * - 指定 ID のポートフォリオを取得して削除
     * - 成功メッセージを表示し、直前のページにリダイレクト
     *
     * @param int $id ポートフォリオID
     * @return \Cake\Http\Response リダイレクトレスポンス
     */
    public function delete($id)
    {
        $this->request->allowMethod(['post','delete']);
        $pf = $this->fetchTable('Portfolios')->get($id);
        $this->fetchTable('Portfolios')->delete($pf);
        $this->Flash->success('削除しました。');

        return $this->redirect($this->referer());
    }
}
