<?php
declare(strict_types=1);

namespace App\Controller;

class ConversationsController extends AppController
{
    /**
     * 会話一覧（自分が当事者の会話）
     */
    public function index()
    {
        $this->loadModel('Conversations');

        // 自分（ユーザー or 会社）
        $actor = $this->getActor(); // ['type'=>'user'|'company','id'=>int]
        $type = $actor['type'] ?? null;
        $id   = $actor['id']   ?? null;

        if (!$type || !$id) {
            $this->Flash->error('ログインしてください。');
            return $this->redirect('/');
        }

        $rows = $this->Conversations->find()
            ->where([
                'OR' => [
                    ['p1_type' => $type, 'p1_id' => $id],
                    ['p2_type' => $type, 'p2_id' => $id],
                ],
            ])
            ->order(['Conversations.modified' => 'DESC'])
            ->all();

        // 相手のエンティティを一括ロード
        $userIds = $companyIds = [];
        foreach ($rows as $c) {
            $isP1Me = ($c->p1_type === $type && (int)$c->p1_id === (int)$id);
            $pType  = $isP1Me ? $c->p2_type : $c->p1_type;
            $pId    = $isP1Me ? (int)$c->p2_id : (int)$c->p1_id;
            if ($pType === 'user')    $userIds[$pId] = true;
            if ($pType === 'company') $companyIds[$pId] = true;
        }

        $Users     = $this->fetchTable('Users');
        $Companies = $this->fetchTable('Companies');

        $userMap = $userIds
            ? $Users->find()->select(['id','name','icon_path'])->where(['id IN' => array_keys($userIds)])->indexBy('id')->toArray()
            : [];

        $companyMap = $companyIds
            ? $Companies->find()->select(['id','name','logo_path'])->where(['id IN' => array_keys($companyIds)])->indexBy('id')->toArray()
            : [];

        // partner を付与
        $conversations = [];
        foreach ($rows as $c) {
            $isP1Me = ($c->p1_type === $type && (int)$c->p1_id === (int)$id);
            $pType  = $isP1Me ? $c->p2_type : $c->p1_type;
            $pId    = $isP1Me ? (int)$c->p2_id : (int)$c->p1_id;

            $partner = null;
            if ($pType === 'user')    $partner = $userMap[$pId]    ?? null;
            if ($pType === 'company') $partner = $companyMap[$pId] ?? null;

            $c->set('partner_type', $pType);
            $c->set('partner', $partner);
            $conversations[] = $c;
        }

        $this->set([
            'conversations' => $conversations,
            'actorType'     => $type,
            'actorId'       => $id,
        ]);
    }

    /**
     * 会話開始
     *  - /conversations/start/2            → user/2 とみなす（後方互換）
     *  - /conversations/start/user/2
     *  - /conversations/start/company/4
     */
    public function start($arg1 = null, $arg2 = null)
    {
        $this->request->allowMethod(['get']);

        if ($arg1 === null) {
            throw new \Cake\Http\Exception\BadRequestException('Missing parameter(s).');
        }

        if ($arg2 === null) {
            $partnerType = 'user';
            $partnerId   = (int)$arg1;
        } else {
            $partnerType = strtolower((string)$arg1);
            $partnerId   = (int)$arg2;
        }

        if (!in_array($partnerType, ['user','company'], true) || $partnerId <= 0) {
            throw new \Cake\Http\Exception\BadRequestException('Invalid type or id.');
        }

        $actor = $this->getActor(); // ['type','id']
        if (empty($actor['type']) || empty($actor['id'])) {
            $this->Flash->error('ログインが必要です。');
            return $this->redirect('/');
        }

        if ($actor['type'] === $partnerType && (int)$actor['id'] === $partnerId) {
            $this->Flash->error('自分自身とは会話できません。');
            return $this->redirect(['action' => 'index']);
        }

        $Conversations = $this->fetchTable('Conversations');

        $conv = $Conversations->find()
            ->where([
                'OR' => [
                    ['p1_type' => $actor['type'], 'p1_id' => $actor['id'], 'p2_type' => $partnerType, 'p2_id' => $partnerId],
                    ['p1_type' => $partnerType, 'p1_id' => $partnerId, 'p2_type' => $actor['type'], 'p2_id' => $actor['id']],
                ],
            ])
            ->first();

        if (!$conv) {
            $conv = $Conversations->newEntity([
                'p1_type' => $actor['type'],
                'p1_id'   => $actor['id'],
                'p2_type' => $partnerType,
                'p2_id'   => $partnerId,
            ]);
            if (!$Conversations->save($conv)) {
                $this->Flash->error('会話を開始できませんでした。');
                return $this->redirect(['action' => 'index']);
            }
        }

        return $this->redirect(['action' => 'view', $conv->id]);
    }

    /**
     * 会話詳細（相手情報とメッセージ一覧）
     */
    public function view($id)
    {
        $Conversations = $this->fetchTable('Conversations');
        $Messages      = $this->fetchTable('Messages');

        $actor = $this->getActor();
        if (empty($actor['id'])) {
            $this->Flash->error('ログインが必要です。');
            return $this->redirect('/');
        }

        $conversation = $Conversations->get($id);

        $isP1 = ($conversation->p1_type === $actor['type'] && (int)$conversation->p1_id === (int)$actor['id']);
        $isP2 = ($conversation->p2_type === $actor['type'] && (int)$conversation->p2_id === (int)$actor['id']);
        if (!$isP1 && !$isP2) {
            throw new \Cake\Http\Exception\ForbiddenException('この会話にはアクセスできません。');
        }

        if ($isP1) {
            $partnerType = $conversation->p2_type;
            $partnerId   = (int)$conversation->p2_id;
        } else {
            $partnerType = $conversation->p1_type;
            $partnerId   = (int)$conversation->p1_id;
        }

        if ($partnerType === 'user') {
            $partner = $this->fetchTable('Users')->find()
                ->select(['id','name','icon_path'])
                ->where(['id' => $partnerId])->first();
        } else {
            $partner = $this->fetchTable('Companies')->find()
                ->select(['id','name','logo_path'])
                ->where(['id' => $partnerId])->first();
        }

        $messages = $Messages->find()
            ->where(['conversation_id' => $id])
            ->orderAsc('created')
            ->all()
            ->toArray();

        $myType = $actor['type'];
        $myId   = (int)$actor['id'];

        $this->set(compact('conversation','messages','partner','myType','myId'));
    }
}
