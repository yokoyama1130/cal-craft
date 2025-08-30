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

        // MessagesController::send の保存直前
        $actor = $this->getActor();
        if ($actor['type'] === 'company') {
            $Companies = $this->fetchTable('Companies');
            $company = $Companies->get($actor['id']);
            $plan = $company->plan ?? 'free';
            $limit = $this->planUserContactLimits[$plan] ?? 0;

            if ($limit > 0) {
                // 相手がユーザーかどうか＆そのユーザーIDを取得
                $Conversations = $this->fetchTable('Conversations');
                $conv = $Conversations->get($conversationId);

                // 相手のタイプとID
                $partnerType = ($conv->p1_type === 'company' && (int)$conv->p1_id === (int)$company->id)
                    ? $conv->p2_type : $conv->p1_type;
                $partnerId = ($conv->p1_type === 'company' && (int)$conv->p1_id === (int)$company->id)
                    ? (int)$conv->p2_id : (int)$conv->p1_id;

                if ($partnerType === 'user') {
                    // 当月、このユーザー宛に会社が既に送っているか？
                    $Messages = $this->fetchTable('Messages');
                    $since = new \DateTime('first day of this month 00:00:00');

                    $alreadyThisMonth = $Messages->find()
                        ->where([
                            'conversation_id' => $conversationId,
                            'sender_type' => 'company',
                            'sender_ref_id' => $company->id,
                            'created >=' => $since,
                        ])
                        ->count() > 0;

                    if (!$alreadyThisMonth) {
                        $used = $this->countMonthlyUniqueUsersContacted((int)$company->id);
                        if ($used >= $limit) {
                            $this->Flash->error('このプランで当月に話しかけ可能なユーザー数の上限に達しました。');
                            return $this->redirect(['controller' => 'Conversations', 'action' => 'view', $conversationId]);
                        }
                    }
                }
            }
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

    // どこか共通で（例: MessagesController/ConversationsController のプロパティとして）
    private array $planUserContactLimits = [
        'free'       => 1,   // 当月 新規に話しかけられるユーザー数
        'pro'        => 100,
        'enterprise' => 0,    // 0は無制限
    ];

    /**
     * 当月、会社が“実際に送信した”相手ユーザーIDのユニーク数を返す
     */
    private function countMonthlyUniqueUsersContacted(int $companyId): int
    {
        $Messages = $this->fetchTable('Messages');
        $Conversations = $this->fetchTable('Conversations');

        // 会社が参加する会話ID（相手がユーザーのもの）の集合
        $convIds = $Conversations->find()
            ->select('id')
            ->where([
                'OR' => [
                    ['p1_type' => 'company', 'p1_id' => $companyId, 'p2_type' => 'user'],
                    ['p2_type' => 'company', 'p2_id' => $companyId, 'p1_type' => 'user'],
                ]
            ])
            ->enableHydration(false)
            ->all()
            ->extract('id')
            ->toList();

        if (!$convIds) {
            return 0;
        }

        $since = new \DateTime('first day of this month 00:00:00');

        // その会話群の中で、会社が送ったメッセージの相手ユーザーIDを集める
        // 相手ユーザーIDは、会話レコードから引く必要があるので2回に分ける
        $p1 = $Conversations->find()
            ->select(['uid' => 'p2_id'])
            ->where(['id IN' => $convIds, 'p1_type' => 'company', 'p2_type' => 'user'])
            ->enableHydration(false)
            ->toArray();
        $p2 = $Conversations->find()
            ->select(['uid' => 'p1_id'])
            ->where(['id IN' => $convIds, 'p2_type' => 'company', 'p1_type' => 'user'])
            ->enableHydration(false)
            ->toArray();

        // 「当月その会話で会社が送ったことがあるか」を Messages で確認
        $sentConvIds = $Messages->find()
            ->select('conversation_id')
            ->where([
                'conversation_id IN' => $convIds,
                'sender_type' => 'company',
                'sender_ref_id' => $companyId,
                'created >=' => $since,
            ])
            ->distinct()
            ->enableHydration(false)
            ->all()
            ->extract('conversation_id')
            ->toList();

        if (!$sentConvIds) {
            return 0;
        }

        // sentConvIds に対応する相手ユーザーIDを抽出
        $partnerUserIds = [];

        if ($sentConvIds) {
            // p1(company)/p2(user) 側
            $rows1 = $Conversations->find()
                ->select(['uid' => 'p2_id'])
                ->where([
                    'id IN' => $sentConvIds,
                    'p1_type' => 'company',
                    'p2_type' => 'user',
                ])
                ->enableHydration(false)
                ->all();
            foreach ($rows1 as $r) $partnerUserIds[$r['uid']] = true;

            // p2(company)/p1(user) 側
            $rows2 = $Conversations->find()
                ->select(['uid' => 'p1_id'])
                ->where([
                    'id IN' => $sentConvIds,
                    'p2_type' => 'company',
                    'p1_type' => 'user',
                ])
                ->enableHydration(false)
                ->all();
            foreach ($rows2 as $r) $partnerUserIds[$r['uid']] = true;
        }

        return count($partnerUserIds);
    }

}
