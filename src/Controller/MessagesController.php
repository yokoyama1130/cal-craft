<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;

class MessagesController extends AppController
{
    /**
     * POST /messages/send
     */
    public function send()
    {
        $this->request->allowMethod(['post']);

        $conversationId = (int)($this->request->getData('conversation_id') ?? 0);
        $rawContent     = (string)($this->request->getData('content') ?? '');
        $content        = trim(preg_replace("/\r\n?/", "\n", $rawContent));

        if ($conversationId <= 0) {
            throw new BadRequestException('conversation_id is required.');
        }
        if ($content === '') {
            $this->Flash->error('メッセージ内容を入力してください。');
            return $this->redirect(['controller' => 'Conversations', 'action' => 'view', $conversationId]);
        }

        $actor = $this->getActor(); // ['type','id']
        if (empty($actor['type']) || empty($actor['id'])) {
            $this->Flash->error('ログインが必要です。');
            return $this->redirect('/');
        }

        $Conversations = $this->fetchTable('Conversations');
        $conv = $Conversations->find()
            ->where(['id' => $conversationId])
            ->first();

        if (!$conv) {
            throw new NotFoundException('Conversation not found.');
        }

        $isParticipant =
            ($conv->p1_type === $actor['type'] && (int)$conv->p1_id === (int)$actor['id']) ||
            ($conv->p2_type === $actor['type'] && (int)$conv->p2_id === (int)$actor['id']);

        if (!$isParticipant) {
            throw new ForbiddenException('You are not a participant of this conversation.');
        }

        $Messages = $this->fetchTable('Messages');
        $msg = $Messages->newEmptyEntity();
        $msg = $Messages->patchEntity($msg, [
            'conversation_id' => $conversationId,
            'content'         => $content,
            'sender_type'     => $actor['type'],  // user | company
            'sender_ref_id'   => $actor['id'],
        ]);

        if (!$Messages->save($msg)) {
            $this->Flash->error('送信に失敗しました。もう一度お試しください。');
            return $this->redirect(['controller' => 'Conversations', 'action' => 'view', $conversationId]);
        }

        // （任意）modified を更新したい場合
        try {
            $conv->set('modified', new \Cake\I18n\FrozenTime());
            $Conversations->save($conv);
        } catch (\Throwable $e) {
            // noop
        }

        return $this->redirect([
            'controller' => 'Conversations',
            'action'     => 'view',
            $conversationId,
            '#'          => 'bottom',
        ]);
    }

    public function delete(int $id)
    {
        $this->request->allowMethod(['post', 'delete']);

        $Messages = $this->fetchTable('Messages');
        $msg = $Messages->find()->where(['id' => $id])->firstOrFail();

        $actor = $this->getActor();
        if (empty($actor['type']) || empty($actor['id'])) {
            throw new ForbiddenException('Unauthorized');
        }

        $isOwner = ($msg->sender_type === $actor['type'] && (int)$msg->sender_ref_id === (int)$actor['id']);
        if (!$isOwner) {
            throw new ForbiddenException('You cannot delete this message.');
        }

        $conversationId = (int)$msg->conversation_id;

        if ($Messages->delete($msg)) {
            $this->Flash->success('メッセージを削除しました。');
        } else {
            $this->Flash->error('メッセージを削除できませんでした。');
        }

        return $this->redirect(['controller' => 'Conversations', 'action' => 'view', $conversationId]);
    }
}
