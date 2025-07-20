<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Conversations Controller
 *
 * @property \App\Model\Table\ConversationsTable $Conversations
 * @method \App\Model\Entity\Conversation[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ConversationsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->loadModel('Conversations');
    
        $userId = $this->request->getAttribute('identity')->get('id');
    
        $conversations = $this->Conversations->find()
            ->where(['OR' => [
                'user1_id' => $userId,
                'user2_id' => $userId
            ]])
            ->contain(['User1', 'User2'])
            ->order(['Conversations.modified' => 'DESC'])
            ->toArray();
    
        // partner を各会話に付与
        foreach ($conversations as $c) {
            $c->partner = ($c->user1_id == $userId) ? $c->user2 : $c->user1;
        }
    
        $this->set(compact('conversations', 'userId'));
    }
    

    public function start($partnerId)
    {
        $this->loadModel('Conversations');

        $userId = $this->request->getAttribute('identity')->get('id');

        if ($userId == $partnerId) {
            $this->Flash->error('自分自身とは会話できません。');
            return $this->redirect(['action' => 'index']);
        }

        // すでに会話があるかチェック
        $conversation = $this->Conversations->find()
            ->where([
                'OR' => [
                    ['user1_id' => $userId, 'user2_id' => $partnerId],
                    ['user1_id' => $partnerId, 'user2_id' => $userId]
                ]
            ])
            ->first();

        if (!$conversation) {
            $conversation = $this->Conversations->newEntity([
                'user1_id' => $userId,
                'user2_id' => $partnerId
            ]);
            $this->Conversations->save($conversation);
        }

        return $this->redirect(['action' => 'view', $conversation->id]);
    }

    public function view($id)
    {
        $this->loadModel('Conversations');
        $this->loadModel('Messages');

        $userId = $this->request->getAttribute('identity')->get('id');

        $conversation = $this->Conversations->find()
            ->where([
                'Conversations.id' => $id,
                'OR' => [
                    'user1_id' => $userId,
                    'user2_id' => $userId
                ]
            ])
            ->contain(['User1', 'User2'])
            ->firstOrFail();

        $messages = $this->Messages->find()
            ->where(['conversation_id' => $id])
            ->contain(['Sender'])
            ->order(['Messages.created' => 'ASC'])
            ->toArray();

        $this->set(compact('conversation', 'messages', 'userId'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $conversation = $this->Conversations->newEmptyEntity();
        if ($this->request->is('post')) {
            $conversation = $this->Conversations->patchEntity($conversation, $this->request->getData());
            if ($this->Conversations->save($conversation)) {
                $this->Flash->success(__('The conversation has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The conversation could not be saved. Please, try again.'));
        }
        $user1 = $this->Conversations->User1->find('list', ['limit' => 200])->all();
        $user2 = $this->Conversations->User2->find('list', ['limit' => 200])->all();
        $this->set(compact('conversation', 'user1', 'user2'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Conversation id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $conversation = $this->Conversations->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $conversation = $this->Conversations->patchEntity($conversation, $this->request->getData());
            if ($this->Conversations->save($conversation)) {
                $this->Flash->success(__('The conversation has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The conversation could not be saved. Please, try again.'));
        }
        $user1 = $this->Conversations->User1->find('list', ['limit' => 200])->all();
        $user2 = $this->Conversations->User2->find('list', ['limit' => 200])->all();
        $this->set(compact('conversation', 'user1', 'user2'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Conversation id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $conversation = $this->Conversations->get($id);
        if ($this->Conversations->delete($conversation)) {
            $this->Flash->success(__('The conversation has been deleted.'));
        } else {
            $this->Flash->error(__('The conversation could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
