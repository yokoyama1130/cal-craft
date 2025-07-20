<?php
declare(strict_types=1);

// src/Controller/MessagesController.php
namespace App\Controller;

class MessagesController extends AppController
{
    public function send()
    {
        if ($this->request->is('post')) {
            $this->loadModel('Messages');

            $data = $this->request->getData();
            $userId = $this->request->getAttribute('identity')->get('id');

            $message = $this->Messages->newEntity([
                'conversation_id' => $data['conversation_id'],
                'sender_id' => $userId,
                'content' => $data['content'],
            ]);

            if ($this->Messages->save($message)) {
                // 成功したらその会話にリダイレクト
                return $this->redirect(['controller' => 'Conversations', 'action' => 'view', $data['conversation_id']]);
            } else {
                $this->Flash->error('メッセージの送信に失敗しました。');
            }
        }

        return $this->redirect($this->referer());
    }
}


