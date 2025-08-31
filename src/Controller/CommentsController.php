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

        $actor = $this->getActor(); // ★ user or company の判定
        if (empty($actor['type']) || empty($actor['id'])) {
            $this->Flash->error('ログインが必要です。');
            return $this->redirect($this->referer());
        }

        $comment = $this->Comments->newEmptyEntity();
        $data    = $this->request->getData();

        // 片方だけセット
        $payload = [
            'portfolio_id' => (int)$data['portfolio_id'],
            'content'      => (string)$data['content'],
            'user_id'      => $actor['type'] === 'user'    ? (int)$actor['id'] : null,
            'company_id'   => $actor['type'] === 'company' ? (int)$actor['id'] : null,
        ];

        $comment = $this->Comments->patchEntity($comment, $payload);

        if ($this->Comments->save($comment)) {
            // 通知（投稿主ユーザーへ）— 既存ロジックを軽微に拡張
            $this->loadModel('Portfolios');
            $portfolio = $this->Portfolios->get($comment->portfolio_id);

            // 自分で自分に通知は送らない
            $selfUserId = ($actor['type'] === 'user') ? (int)$actor['id'] : 0;
            if ((int)$portfolio->user_id !== $selfUserId) {
                $this->loadModel('Notifications');

                // 既存が sender_id だけなら:
                // - 会社コメント時は sender_id を NULL にしておく or 別カラムを追加
                $notification = $this->Notifications->newEmptyEntity();
                $notification = $this->Notifications->patchEntity($notification, [
                    'user_id'       => (int)$portfolio->user_id, // 受け取り側（投稿主）
                    'sender_id'     => $actor['type'] === 'user' ? (int)$actor['id'] : null, // 互換用
                    // 拡張するなら下の2つのカラムを追加して使うのがベター
                    // 'sender_type'    => $actor['type'],
                    // 'sender_company_id' => $actor['type'] === 'company' ? (int)$actor['id'] : null,
                    'portfolio_id'  => (int)$portfolio->id,
                    'type'          => 'comment',
                    'is_read'       => false,
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
        $actor   = $this->getActor();

        // 自分のコメントだけ編集可（user_id or company_id のどちらか一致）
        $isOwner =
            ($actor['type'] === 'user'    && (int)$comment->user_id === (int)$actor['id']) ||
            ($actor['type'] === 'company' && (int)$comment->company_id === (int)$actor['id']);

        if (!$isOwner) {
            $this->Flash->error('編集できません。');
            return $this->redirect($this->referer());
        }

        if ($this->request->is(['post','put','patch'])) {
            $this->Comments->patchEntity($comment, [
                'content' => (string)$this->request->getData('content')
            ]);
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
        $this->request->allowMethod(['post', 'delete']);
        $comment = $this->Comments->get($id);
        $actor   = $this->getActor();

        $isOwner =
            ($actor['type'] === 'user'    && (int)$comment->user_id === (int)$actor['id']) ||
            ($actor['type'] === 'company' && (int)$comment->company_id === (int)$actor['id']);

        if (!$isOwner) {
            $this->Flash->error('削除できません。');
            return $this->redirect($this->referer());
        }

        $portfolioId = (int)$comment->portfolio_id;

        if ($this->Comments->delete($comment)) {
            $this->Flash->success('コメントを削除しました。');
        } else {
            $this->Flash->error('削除に失敗しました。');
        }

        return $this->redirect(['controller' => 'Portfolios', 'action' => 'view', $portfolioId]);
    }

}
