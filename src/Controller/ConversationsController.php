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
    public function index()
    {
        $this->loadModel('Conversations');

        [$type, $id] = (function($a){ return [$a['type'],$a['id']]; })($this->getActor());
        if (!$type || !$id) {
            $this->Flash->error('ログインしてください。');
            return $this->redirect('/');
        }

        $convos = $this->Conversations->find()
            ->where([
                'OR' => [
                    ['p1_type' => $type, 'p1_id' => $id],
                    ['p2_type' => $type, 'p2_id' => $id],
                ]
            ])
            ->order(['Conversations.modified' => 'DESC'])
            ->all();

        // 相手エンティティをまとめてプリロード
        $userIds = $companyIds = [];
        foreach ($convos as $c) {
            $isP1Me = ($c->p1_type === $type && (int)$c->p1_id === $id);
            $pType  = $isP1Me ? $c->p2_type : $c->p1_type;
            $pId    = $isP1Me ? (int)$c->p2_id : (int)$c->p1_id;
            if ($pType === 'user')    $userIds[$pId] = true;
            if ($pType === 'company') $companyIds[$pId] = true;
        }
        $Users = $this->fetchTable('Users');
        $Companies = $this->fetchTable('Companies');
        $userMap = $userIds ? $Users->find()->where(['id IN' => array_keys($userIds)])->indexBy('id')->toArray() : [];
        $companyMap = $companyIds ? $Companies->find()->where(['id IN' => array_keys($companyIds)])->indexBy('id')->toArray() : [];

        // partner をくっつける（ビューが使いやすいように）
        $conversations = [];
        foreach ($convos as $c) {
            $isP1Me = ($c->p1_type === $type && (int)$c->p1_id === $id);
            $pType  = $isP1Me ? $c->p2_type : $c->p1_type;
            $pId    = $isP1Me ? (int)$c->p2_id : (int)$c->p1_id;

            $partner = null;
            if ($pType === 'user')    $partner = $userMap[$pId]    ?? null;
            if ($pType === 'company') $partner = $companyMap[$pId] ?? null;

            // ビュー互換のために最低限 name/icon_path っぽいものを束ねる
            if ($partner) {
                $c->set('partner_type', $pType);
                $c->set('partner', $partner);
            }
            $conversations[] = $c;
        }

        $this->set([
            'conversations' => $conversations,
            'actorType' => $type,
            'actorId' => $id,
        ]);
    }

    // 開始: /conversations/start/{partnerType}/{partnerId}
    public function start(string $partnerType, int $partnerId)
    {
        $this->loadModel('Conversations');
        $actor = $this->getActor();
        if (!$actor['type'] || !$actor['id']) {
            $this->Flash->error('ログインしてください。');
            return $this->redirect(['action' => 'index']);
        }

        // 自分自身とは開始不可
        if ($actor['type'] === $partnerType && $actor['id'] === $partnerId) {
            $this->Flash->error('自分自身とは会話できません。');
            return $this->redirect(['action' => 'index']);
        }

        // 既存チェック（順不同）
        $conversation = $this->Conversations->find()
            ->where([
                'OR' => [
                    ['p1_type'=>$actor['type'],'p1_id'=>$actor['id'],'p2_type'=>$partnerType,'p2_id'=>$partnerId],
                    ['p1_type'=>$partnerType,'p1_id'=>$partnerId,'p2_type'=>$actor['type'],'p2_id'=>$actor['id']],
                ]
            ])
            ->first();

        if (!$conversation) {
            $conversation = $this->Conversations->newEntity([
                'p1_type' => $actor['type'],
                'p1_id'   => $actor['id'],
                'p2_type' => $partnerType,
                'p2_id'   => $partnerId,
            ]);
            $this->Conversations->saveOrFail($conversation);
        }

        return $this->redirect(['action' => 'view', $conversation->id]);
    }

    public function view($id)
    {
        $this->loadModel('Conversations');
        $this->loadModel('Messages');

        $actor = $this->getActor();
        if (!$actor['type'] || !$actor['id']) {
            $this->Flash->error('ログインしてください。');
            return $this->redirect(['action' => 'index']);
        }

        $c = $this->Conversations->find()
            ->where([
                'Conversations.id' => $id,
                'OR' => [
                    ['p1_type'=>$actor['type'],'p1_id'=>$actor['id']],
                    ['p2_type'=>$actor['type'],'p2_id'=>$actor['id']],
                ]
            ])
            ->firstOrFail();

        // 相手側のタイプ/ID
        $isP1Me = ($c->p1_type === $actor['type'] && (int)$c->p1_id === $actor['id']);
        $partnerType = $isP1Me ? $c->p2_type : $c->p1_type;
        $partnerId   = $isP1Me ? (int)$c->p2_id : (int)$c->p1_id;

        // 相手エンティティ
        $partner = null;
        if ($partnerType === 'user') {
            $partner = $this->fetchTable('Users')->get($partnerId);
        } else {
            $partner = $this->fetchTable('Companies')->get($partnerId);
        }

        // メッセージ取得（送信者は polymorphic）
        $rows = $this->Messages->find()
            ->where(['conversation_id' => $id])
            ->order(['Messages.created' => 'ASC'])
            ->toArray();

        // 送信者をまとめて解決（N+1防止）
        $userIds = $companyIds = [];
        foreach ($rows as $m) {
            if ($m->sender_type === 'user')    $userIds[(int)$m->sender_id] = true;
            if ($m->sender_type === 'company') $companyIds[(int)$m->sender_id] = true;
        }
        $Users = $this->fetchTable('Users');
        $Companies = $this->fetchTable('Companies');
        $userMap = $userIds ? $Users->find()->where(['id IN' => array_keys($userIds)])->indexBy('id')->toArray() : [];
        $companyMap = $companyIds ? $Companies->find()->where(['id IN' => array_keys($companyIds)])->indexBy('id')->toArray() : [];

        // ビュー用の sender を付与
        $messages = [];
        foreach ($rows as $m) {
            $sender = ($m->sender_type === 'user') ? ($userMap[(int)$m->sender_id] ?? null)
                                                   : ($companyMap[(int)$m->sender_id] ?? null);
            $m->set('sender', $sender);
            $messages[] = $m;
        }

        $this->set([
            'conversation' => $c,
            'messages' => $messages,
            // ビュー互換のため
            'userId' => $actor['id'], // 送受判定に使っているのでIDだけ渡す
            'partner' => $partner,
        ]);
    }
}
