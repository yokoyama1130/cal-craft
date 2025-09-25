<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\I18n\FrozenTime;

class UsersController extends AppController
{
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
            if ((int)$q['active'] === 1) $query->where(['Users.deleted_at IS' => null]);
            else $query->where(['Users.deleted_at IS NOT' => null]);
        }

        $this->paginate = ['limit' => 30];
        $users = $this->paginate($query);
        $this->set(compact('users', 'q'));
    }

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
