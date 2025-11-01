<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;
use Cake\Routing\Router;

class ConversationsController extends AppController
{
    private bool $forceBypass = true;

    /**
     * コントローラ初期化フック。
     *
     * 以下を行います:
     * - 必要なテーブル（Conversations / Messages / Users / Companies）を取得。
     * - ビューレイヤーを JSON 用に設定。
     * - Authentication コンポーネントが存在し、開発用のバイパス ($this->forceBypass) が有効な場合は
     *   `index` アクションのみ未認証アクセスを許可。
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->Conversations = $this->fetchTable('Conversations');
        $this->Messages = $this->fetchTable('Messages');
        $this->Users = $this->fetchTable('Users');
        $this->Companies = $this->fetchTable('Companies');

        $this->viewBuilder()->setClassName('Json');

        // DMは原則ログイン必須。ただし開発中はバイパス許可
        if ($this->components()->has('Authentication') && $this->forceBypass) {
            $this->Authentication->allowUnauthenticated(['index']);
        }
    }

    /**
     * 各アクション実行前のフィルタ処理。
     *
     * - 認証関連のアクセス制御は initialize() 内で行うため、
     *   本メソッドでは特別な処理は行わない。
     *
     * @param \Cake\Event\EventInterface $event イベントオブジェクト
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        // allowUnauthenticated は initialize() 側で制御
    }

    /**
     * 認証主体（actor）を特定する。
     * - 認証あり: identity から type/id を推定
     * - 認証なし + forceBypass: ?as_uid / ?as_company_id で擬似ログイン
     *
     * @return array{type:string,id:int}|\Cake\Http\Response
     */
    private function resolveActor()
    {
        // 1) 認証から取得
        $identity = $this->request->getAttribute('identity');
        if ($identity) {
            // identity に type があればそれを優先
            $type = (string)($identity['type'] ?? $identity->get('type') ?? '');
            $id = (int)($identity['id'] ?? $identity->get('id') ?? 0);

            if ($type !== '' && $id > 0) {
                return ['type' => $type, 'id' => $id];
            }

            // type 不明なら Users/Companies どちらにあるかを判定
            if ($id > 0) {
                if ($this->Users->exists(['id' => $id])) {
                    return ['type' => 'user', 'id' => $id];
                }
                if ($this->Companies->exists(['id' => $id])) {
                    return ['type' => 'company', 'id' => $id];
                }
            }
        }

        // 2) 開発用バイパス
        if ($this->forceBypass) {
            $asUid = (int)$this->request->getQuery('as_uid', 0);
            if ($asUid > 0) {
                return ['type' => 'user', 'id' => $asUid];
            }
            $asCid = (int)$this->request->getQuery('as_company_id', 0);
            if ($asCid > 0) {
                return ['type' => 'company', 'id' => $asCid];
            }
        }

        // 3) どれでも無ければ 401 JSON
        $this->response = $this->response->withStatus(401);
        $this->set(['success' => false, 'message' => 'Unauthorized']);
        $this->viewBuilder()->setOption('serialize', ['success', 'message']);

        return $this->response;
    }

    /**
     * 会話一覧取得API
     *
     * @return void JSONで会話一覧を返す（success, items）
     */
    public function index()
    {
        $this->request->allowMethod(['get']);

        $actor = $this->resolveActor();
        if ($actor instanceof \Cake\Http\Response) {
            // 401 などをすでにセット済み
            return;
        }
        $actorType = $actor['type']; // 'user' or 'company'
        $actorId = (int)$actor['id'];

        // 2. 自分が参加している会話一覧（p1/p2 対応）
        $rows = $this->Conversations->find()
            ->where([
                'OR' => [
                    ['p1_type' => $actorType, 'p1_id' => $actorId],
                    ['p2_type' => $actorType, 'p2_id' => $actorId],
                ],
            ])
            ->order(['Conversations.modified' => 'DESC'])
            ->all()
            ->toArray();

        if (!$rows) {
            $this->set(['success' => true, 'items' => []]);
            $this->viewBuilder()->setOption('serialize', ['success', 'items']);

            return;
        }

        // 3. 相手のIDを収集
        $userIds = [];
        $companyIds = [];
        foreach ($rows as $c) {
            $iAmP1 = ($c->p1_type === $actorType && (int)$c->p1_id === $actorId);

            $partnerType = $iAmP1 ? $c->p2_type : $c->p1_type;
            $partnerId = $iAmP1 ? (int)$c->p2_id : (int)$c->p1_id;

            if ($partnerType === 'user' && $partnerId > 0) {
                $userIds[$partnerId] = true;
            } elseif ($partnerType === 'company' && $partnerId > 0) {
                $companyIds[$partnerId] = true;
            }
        }

        // まとめて取得
        $userMap = [];
        if ($userIds) {
            $userMap = $this->Users->find()
                ->select(['id','name','icon_path'])
                ->where(['id IN' => array_keys($userIds)])
                ->indexBy('id')
                ->toArray();
        }

        $companyMap = [];
        if ($companyIds) {
            $companyMap = $this->Companies->find()
                ->select(['id','name','logo_path'])
                ->where(['id IN' => array_keys($companyIds)])
                ->indexBy('id')
                ->toArray();
        }

        // 4. 各会話の最新メッセージを1件ずつ
        $latestMap = [];
        foreach ($rows as $c) {
            $m = $this->Messages->find()
                ->where(['conversation_id' => (int)$c->id])
                ->orderDesc('created')
                ->limit(1)
                ->first();
            if ($m) {
                $latestMap[(int)$c->id] = $m;
            }
        }

        // 5. レスポンス組み立て
        $items = [];
        foreach ($rows as $c) {
            $iAmP1 = ($c->p1_type === $actorType && (int)$c->p1_id === $actorId);

            $partnerType = $iAmP1 ? $c->p2_type : $c->p1_type;
            $partnerId = $iAmP1 ? (int)$c->p2_id : (int)$c->p1_id;

            $partnerName = '';
            $partnerIcon = '';

            if ($partnerType === 'user') {
                $u = $userMap[$partnerId] ?? null;
                if ($u) {
                    $partnerName = (string)($u->name ?? '');
                    $iconPath = (string)($u->icon_path ?? '');
                    if ($iconPath !== '') {
                        $partnerIcon = Router::url('/img/' . ltrim($iconPath, '/'), true);
                    }
                }
            } elseif ($partnerType === 'company') {
                $co = $companyMap[$partnerId] ?? null;
                if ($co) {
                    $partnerName = (string)($co->name ?? '');
                    $logo = (string)($co->logo_path ?? '');
                    if ($logo !== '') {
                        $partnerIcon = Router::url('/img/' . ltrim($logo, '/'), true);
                    }
                }
            }

            $latest = $latestMap[(int)$c->id] ?? null;

            // ←←← 修正ポイント：本文を多段フォールバック
            $lastMessageText = '';
            $lastTimeStr = '';
            $fromMe = false;

            if ($latest) {
                // 本文の候補（あなたのMessagesテーブルに合わせて必要なら候補を足してください）
                $candidates = [
                    $latest->body ?? null,
                    $latest->content ?? null,
                    $latest->message ?? null,
                    $latest->text ?? null,
                ];
                foreach ($candidates as $cand) {
                    if (is_string($cand) && trim($cand) !== '') {
                        $lastMessageText = (string)$cand;
                        break;
                    }
                }

                // 添付があるなら簡易ラベル（カラム名はプロジェクトに合わせて）
                if ($lastMessageText === '') {
                    if (!empty($latest->image_path) || !empty($latest->image_url)) {
                        $lastMessageText = '[画像]';
                    } elseif (!empty($latest->file_path) || !empty($latest->file_url)) {
                        $lastMessageText = '[ファイル]';
                    } elseif (!empty($latest->stamp_code)) {
                        $lastMessageText = '[スタンプ]';
                    }
                }

                // 送信者が自分か（任意：使わないなら削ってOK）
                $fromMe = (
                    ($latest->sender_type ?? null) === $actorType &&
                    (int)($latest->sender_id ?? 0) === $actorId
                );

                if ($latest->created) {
                    $t = $latest->created;
                    $lastTimeStr = $t instanceof FrozenTime
                        ? $t->i18nFormat('yyyy-MM-dd HH:mm')
                        : (string)$t;
                }
            }

            $items[] = [
                'conversation_id' => (int)$c->id,
                'partner_type' => $partnerType, // 'user' or 'company'
                'partner_id' => $partnerId,
                'partner_name' => $partnerName,
                'partner_icon_url' => $partnerIcon,
                'last_message' => $lastMessageText, // ← ここは文字列として返す
                'last_time' => $lastTimeStr,
                'from_me' => $fromMe, // ← 任意
            ];
        }

        $this->set(['success' => true, 'items' => $items]);
        // ★ 重要：return はしない。JsonView が自動出力
        $this->viewBuilder()->setOption('serialize', ['success', 'items']);
    }
}
