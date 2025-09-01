<?php
namespace App\Controller\Admin;

class CommentsController extends AppController
{
    public function index()
    {
        $q = $this->request->getQuery('q');
        $Comments = $this->fetchTable('Comments');

        $query = $Comments->find()
            ->contain(['Users','Companies','Portfolios'])
            ->order(['Comments.created'=>'DESC']);

        if ($q) {
            $kw = '%' . str_replace('%','\%',$q) . '%';
            $query->where(['Comments.content LIKE' => $kw]);
        }

        $this->paginate = ['limit'=>30];
        $comments = $this->paginate($query);
        $this->set(compact('comments','q'));
    }

    public function delete($id)
    {
        $this->request->allowMethod(['post','delete']);
        $c = $this->fetchTable('Comments')->get($id);
        $this->fetchTable('Comments')->delete($c);
        $this->Flash->success('コメントを削除しました。');
        return $this->redirect($this->referer());
    }
}
