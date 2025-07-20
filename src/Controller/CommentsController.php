<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Comments Controller
 *
 * @property \App\Model\Table\CommentsTable $Comments
 * @method \App\Model\Entity\Comment[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CommentsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['Users', 'Portfolios'],
        ];
        $comments = $this->paginate($this->Comments);

        $this->set(compact('comments'));
    }

    /**
     * View method
     *
     * @param string|null $id Comment id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $comment = $this->Comments->get($id, [
            'contain' => ['Users', 'Portfolios'],
        ]);

        $this->set(compact('comment'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->request->allowMethod(['post']);
        $comment = $this->Comments->newEmptyEntity();
        $data = $this->request->getData();
        $comment = $this->Comments->patchEntity($comment, $data);
        $comment->user_id = $this->request->getAttribute('identity')->get('id');

        if ($this->Comments->save($comment)) {
            // 通知追加
            $this->loadModel('Portfolios');
            $portfolio = $this->Portfolios->get($comment->portfolio_id);

            if ($portfolio->user_id !== $comment->user_id) {
                $this->loadModel('Notifications');
                $notification = $this->Notifications->newEntity([
                    'user_id' => $portfolio->user_id,       // 通知受け取る側（投稿主）
                    'sender_id' => $comment->user_id,       // 通知送る側（コメント主）
                    'portfolio_id' => $portfolio->id,
                    'type' => 'comment',
                    'is_read' => false,
                ]);
                $this->Notifications->save($notification);
            }

            $this->Flash->success('コメントを投稿しました。');
        } else {
            $this->Flash->error('コメントの投稿に失敗しました。');
        }

        return $this->redirect($this->referer());
    }

    public function edit($id)
    {
        $comment = $this->Comments->get($id);
        $userId = $this->request->getAttribute('identity')->get('id');

        if ($comment->user_id !== $userId) {
            $this->Flash->error('編集できません。');
            return $this->redirect($this->referer());
        }

        if ($this->request->is(['post', 'put', 'patch'])) {
            $this->Comments->patchEntity($comment, $this->request->getData());
            if ($this->Comments->save($comment)) {
                $this->Flash->success('コメントを更新しました。');
                return $this->redirect(['controller' => 'Portfolios', 'action' => 'view', $comment->portfolio_id]);
            }
            $this->Flash->error('コメントの更新に失敗しました。');
        }

        $this->set(compact('comment'));
    }

    public function delete($id)
    {
        $comment = $this->Comments->get($id);
        $userId = $this->request->getAttribute('identity')->get('id');

        if ($comment->user_id !== $userId) {
            $this->Flash->error('削除できません。');
            return $this->redirect($this->referer());
        }

        $this->request->allowMethod(['post', 'delete']);
        if ($this->Comments->delete($comment)) {
            $this->Flash->success('コメントを削除しました。');
        } else {
            $this->Flash->error('削除に失敗しました。');
        }

        return $this->redirect(['controller' => 'Portfolios', 'action' => 'view', $comment->portfolio_id]);
    }

}
