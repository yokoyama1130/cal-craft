<?php
namespace App\Controller\Admin;

use App\Controller\Admin\AppController;

class PortfoliosController extends AppController
{
    public function index()
    {
        $this->loadModel('Portfolios');
        $this->loadModel('Users');

        // クエリから user_id を取得
        $userId = $this->request->getQuery('user_id');

        // ユーザー一覧配列（セレクトボックス用）
        $users = $this->Users->find('list', [
            'keyField' => 'id',
            'valueField' => 'name'
        ])->toArray();

        // 投稿の取得（user_id 指定時は絞り込み）
        $query = $this->Portfolios->find()->contain(['Users']);
        if (!empty($userId)) {
            $query->where(['user_id' => $userId]);
        }

        $portfolios = $this->paginate($query);

        $this->set(compact('portfolios', 'users', 'userId'));
    }

    public function toggleVisibility($id = null)
    {
        $this->request->allowMethod(['post']);
        $portfolio = $this->Portfolios->get($id);
        $portfolio->is_public = !$portfolio->is_public;
        if ($this->Portfolios->save($portfolio)) {
            $this->Flash->success('公開状態を切り替えました。');
        } else {
            $this->Flash->error('切り替えに失敗しました。');
        }

        // クエリパラメータを維持してリダイレクト
        return $this->redirect($this->referer());
    }

    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $portfolio = $this->Portfolios->get($id);
        if ($this->Portfolios->delete($portfolio)) {
            $this->Flash->success('投稿を削除しました。');
        } else {
            $this->Flash->error('削除に失敗しました。');
        }

        // クエリパラメータを維持してリダイレクト
        return $this->redirect($this->referer());
    }
}
