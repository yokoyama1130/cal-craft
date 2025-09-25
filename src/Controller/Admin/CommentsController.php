<?php
declare(strict_types=1);

namespace App\Controller\Admin;

class CommentsController extends AppController
{
    /**
     * コメント一覧を表示する管理画面アクション。
     *
     * - クエリパラメータ q があれば content を LIKE 検索
     * - Users, Companies, Portfolios を contain して取得
     * - 作成日の降順で並べ替え
     * - ページネーション（1ページ30件）
     *
     * @return void
     */
    public function index()
    {
        $q = $this->request->getQuery('q');
        $Comments = $this->fetchTable('Comments');

        $query = $Comments->find()
            ->contain(['Users','Companies','Portfolios'])
            ->order(['Comments.created' => 'DESC']);

        if ($q) {
            $kw = '%' . str_replace('%', '\%', $q) . '%';
            $query->where(['Comments.content LIKE' => $kw]);
        }

        $this->paginate = ['limit' => 30];
        $comments = $this->paginate($query);
        $this->set(compact('comments', 'q'));
    }

    /**
     * コメント削除処理（管理者用）。
     *
     * - POST または DELETE リクエストのみ許可
     * - 指定 ID のコメントを取得して削除
     * - 成功後は成功メッセージを表示し、直前のページにリダイレクト
     *
     * @param int $id コメントID
     * @return \Cake\Http\Response リダイレクトレスポンス
     */
    public function delete($id)
    {
        $this->request->allowMethod(['post','delete']);
        $c = $this->fetchTable('Comments')->get($id);
        $this->fetchTable('Comments')->delete($c);
        $this->Flash->success('コメントを削除しました。');

        return $this->redirect($this->referer());
    }
}
