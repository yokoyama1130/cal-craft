<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\I18n\FrozenTime;

class UsersController extends AppController
{
    /**
     * ユーザー一覧を表示する管理画面アクション。
     *
     * - クエリパラメータに応じて検索条件を付与
     *   - q: 名前またはメールアドレスで部分一致検索
     *   - active: 1 → 有効（削除されていない）ユーザー、0 → 削除済みユーザー
     * - ID の降順で並び替え
     * - ページネーション（1ページ30件）
     *
     * @return void
     */
    public function index()
    {
        $q = $this->request->getQuery();
        $Users = $this->fetchTable('Users');

        $query = $Users->find()->order(['id' => 'DESC']);

        if (!empty($q['q'])) {
            $kw = '%' . str_replace('%', '\%', $q['q']) . '%';
            $query->where(['OR' => [
                'Users.name LIKE' => $kw,
                'Users.email LIKE' => $kw,
            ]]);
        }
        if (($q['active'] ?? '') !== '') {
            if ((int)$q['active'] === 1) {
                $query->where(['Users.deleted_at IS' => null]);
            } else {
                $query->where(['Users.deleted_at IS NOT' => null]);
            }
        }

        $this->paginate = ['limit' => 30];
        $users = $this->paginate($query);
        $this->set(compact('users', 'q'));
    }

    /**
     * ユーザーの状態（有効／無効）を切り替える。
     *
     * - POST リクエストのみ許可
     * - 指定 ID のユーザーを取得
     * - deleted_at が NULL の場合は現在時刻を設定 → 無効化
     * - deleted_at がセット済みの場合は NULL に戻す → 有効化
     * - 成功メッセージを表示し、直前のページにリダイレクト
     *
     * @param int $id ユーザーID
     * @return \Cake\Http\Response リダイレクトレスポンス
     */
    public function toggle($id)
    {
        $this->request->allowMethod(['post']);
        $Users = $this->fetchTable('Users');
        $u = $Users->get($id);
        $u->deleted_at = $u->deleted_at ? null : FrozenTime::now();
        $Users->save($u);
        $this->Flash->success('ユーザー状態を切り替えました。');

        return $this->redirect($this->referer());
    }
}
