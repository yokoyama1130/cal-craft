<?php
declare(strict_types=1);

namespace App\Controller;

class ConversationsController extends AppController
{
    /**
     * 会話一覧アクション
     *
     * ログイン中のユーザーまたは企業が当事者となっている会話を取得し、
     * 相手（partner）の情報を付与して一覧表示します。
     *
     * 主な処理の流れ:
     * - 自分（actor: user または company）の ID を取得
     * - Conversations テーブルから自分が p1 または p2 となっている会話を検索
     * - 相手となる user_id / company_id を一括収集し、Users / Companies テーブルから情報をロード
     * - 各会話エンティティに partner_type と partner 情報を付加
     * - conversations, actorType, actorId をビューに渡す
     *
     * 未ログインの場合はトップページにリダイレクトし、エラーメッセージを表示します。
     *
     * @return \Cake\Http\Response|null レスポンス（未ログイン時はリダイレクト）
     */
    public function index()
    {
        $this->Conversations = $this->fetchTable('Conversations');

        // 自分（ユーザー or 会社）
        $actor = $this->getActor(); // ['type'=>'user'|'company','id'=>int]
        $type = $actor['type'] ?? null;
        $id = $actor['id'] ?? null;

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
            $pType = $isP1Me ? $c->p2_type : $c->p1_type;
            $pId = $isP1Me ? (int)$c->p2_id : (int)$c->p1_id;
            if ($pType === 'user') {
                $userIds[$pId] = true;
            }
            if ($pType === 'company') {
                $companyIds[$pId] = true;
            }
        }

        $Users = $this->fetchTable('Users');
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
            $pType = $isP1Me ? $c->p2_type : $c->p1_type;
            $pId = $isP1Me ? (int)$c->p2_id : (int)$c->p1_id;

            $partner = null;
            if ($pType === 'user') $partner = $userMap[$pId] ?? null;
            if ($pType === 'company') $partner = $companyMap[$pId] ?? null;

            $c->set('partner_type', $pType);
            $c->set('partner', $partner);
            $conversations[] = $c;
        }

        $this->set([
            'conversations' => $conversations,
            'actorType' => $type,
            'actorId' => $id,
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
            $partnerId = (int)$arg1;
        } else {
            $partnerType = strtolower((string)$arg1);
            $partnerId = (int)$arg2;
        }

        if (!in_array($partnerType, ['user','company'], true) || $partnerId <= 0) {
            throw new \Cake\Http\Exception\BadRequestException('Invalid type or id.');
        }

        $actor = $this->getActor(); // ['type','id']
        if (empty($actor['type']) || empty($actor['id'])) {
            $this->Flash->error('ログインが必要です。');

            return $this->redirect('/');
        }

        // start() の「convが無いので新規作成」直前あたり
        if ($actor['type'] === 'company' && $partnerType === 'user') {
            $company = $this->fetchTable('Companies')->get($actor['id']);
            $plan = $company->plan ?? 'free';
            $limit = $this->planUserContactLimits[$plan] ?? 0; // 0は無制限

            if ($limit > 0) {
                $used = $this->countMonthlyUniqueUsersContacted((int)$company->id);
                if ($used >= $limit) {
                    $this->Flash->error('このプランで当月に話しかけ可能なユーザー数の上限に達しました。');

                    return $this->redirect(['action' => 'index']);
                }
            }
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
                'p1_id' => $actor['id'],
                'p2_type' => $partnerType,
                'p2_id' => $partnerId,
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
        $Messages = $this->fetchTable('Messages');

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
            $partnerId = (int)$conversation->p2_id;
        } else {
            $partnerType = $conversation->p1_type;
            $partnerId = (int)$conversation->p1_id;
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
        $myId = (int)$actor['id'];

        $this->set(compact('conversation', 'messages', 'partner', 'myType', 'myId'));
    }

    // プラン別：当月に新規コンタクトできるユーザー数（0は無制限
    private array $planUserContactLimits = [
        'free' => 1,
        'pro' => 100,
        'enterprise' => 0,
    ];

    /**
     * 当月、会社が“実際に送信した”相手ユーザーIDのユニーク数を返す
     */
    private function countMonthlyUniqueUsersContacted(int $companyId): int
    {
        $Messages = $this->fetchTable('Messages');
        $Conversations = $this->fetchTable('Conversations');

        // 会社が参加する会話（相手がユーザー）
        $convIds = $Conversations->find()
            ->select('id')
            ->where([
                'OR' => [
                    ['p1_type' => 'company', 'p1_id' => $companyId, 'p2_type' => 'user'],
                    ['p2_type' => 'company', 'p2_id' => $companyId, 'p1_type' => 'user'],
                ],
            ])
            ->enableHydration(false)
            ->all()
            ->extract('id')
            ->toList();

        if (!$convIds) {
            return 0;
        }

        $since = new \DateTime('first day of this month 00:00:00');

        // 当月、会社が送ったメッセージがある会話ID
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

        // 送った会話ID → 相手ユーザーID を集計
        $partnerUserIds = [];

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

        return count($partnerUserIds);
    }
}
