<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\Http\Exception\NotFoundException;

class MessagesController extends AppController
{
    private bool $forceBypass = true;

    /**
     * コントローラ初期化メソッド。
     *
     * 以下の初期設定を行う:
     * - ビュービルダーを JSON 出力用に設定。
     * - Authentication コンポーネントを読み込み。
     * - 開発用バイパス ($this->forceBypass) が有効な場合は、
     *   `index` および `send` アクションへの未認証アクセスを許可。
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->viewBuilder()->setClassName('Json');
        $this->loadComponent('Authentication.Authentication');

        if ($this->forceBypass) {
            $this->Authentication->allowUnauthenticated(['index', 'send']);
        }
    }

    /**
     * 認証エラー(401 Unauthorized)用のJSONレスポンスを生成する。
     *
     * 指定されたメッセージを含むエラーレスポンスを返し、
     * HTTPステータスコードを 401 に設定する。
     *
     * @param string $message エラーメッセージ（省略時は "Authentication is required to continue"）
     * @return \Cake\Http\Response 401ステータス付きのレスポンスオブジェクト
     */
    private function json401(string $message = 'Authentication is required to continue')
    {
        $this->set(['error' => ['message' => $message, 'code' => 401]]);
        $this->viewBuilder()->setOption('serialize', ['error']);

        return $this->response->withStatus(401);
    }

    /**
     * リクエスト不正(400 Bad Request)用のJSONレスポンスを生成する。
     *
     * 指定されたメッセージを含むエラーレスポンスを返し、
     * HTTPステータスコードを 400 に設定する。
     *
     * @param string $message エラーメッセージ
     * @return \Cake\Http\Response 400ステータス付きのレスポンスオブジェクト
     */
    private function json400(string $message)
    {
        $this->set(['error' => ['message' => $message, 'code' => 400]]);
        $this->viewBuilder()->setOption('serialize', ['error']);

        return $this->response->withStatus(400);
    }

    /**
     * 現在のリクエストから認証済みユーザーIDを取得する。
     *
     * - 認証が有効な場合は、ログイン中ユーザーのIDを返す。
     * - 未認証または無効な認証情報の場合は、401エラーレスポンス(JSON)を返す。
     * - 開発用バイパス ($this->forceBypass) が有効な場合は、
     *   クエリパラメータ `as_uid` の値をユーザーIDとして使用できる。
     *
     * @return int|\Cake\Http\Response ユーザーID、または401レスポンスオブジェクト
     */
    private function requireUserId()
    {
        $result = $this->Authentication->getResult();
        if ($result && $result->isValid()) {
            /** @var \ArrayAccess $identity */
            $identity = $this->request->getAttribute('identity');
            $uid = (int)($identity['id'] ?? 0);

            return $uid > 0 ? $uid : $this->json401('Invalid identity');
        }
        if ($this->forceBypass) {
            $asUid = (int)$this->request->getQuery('as_uid', 0);
            if ($asUid > 0) {
                return $asUid;
            }
        }

        return $this->json401();
    }

    /**
     * 指定したユーザーIDを会話の参加条件として返す。
     *
     * Conversations テーブル検索時に使用する WHERE 条件配列を生成する。
     * 旧スキーマ（user1_id / user2_id）にも対応しており、
     * p1/p2 カラムおよび旧フィールドの両方を考慮する。
     *
     * @param int $uid ユーザーID
     * @return array CakePHP ORM で使用する OR 条件配列
     */
    private function participantCondition(int $uid): array
    {
        return [
            'OR' => [
                ['p1_type' => 'user', 'p1_id' => $uid],
                ['p2_type' => 'user', 'p2_id' => $uid],
                ['user1_id' => $uid], // 旧スキーマ互換
                ['user2_id' => $uid],
            ],
        ];
    }

    /**
     * textColumn
     *
     * @return string 使用すべき本文カラム名（content または body）
     */
    private function textColumn(): string
    {
        $schema = $this->fetchTable('Messages')->getSchema();
        if ($schema->hasColumn('content')) {
            return 'content';
        }

        if ($schema->hasColumn('body')) {
            return 'body';
        }

        return 'content';
    }

    /**
     * エンティティから本文テキストを取得する。
     *
     * - 現行スキーマのテキストカラム（content または body）を動的に取得。
     * - 該当カラムが空の場合は、旧スキーマ互換としてもう一方のカラムを参照。
     *
     * @param \Cake\Datasource\EntityInterface|object $entity 対象エンティティ
     * @return string 本文テキスト（存在しない場合は空文字）
     */
    private function readText($entity): string
    {
        $col = $this->textColumn();
        $v = (string)($entity->$col ?? '');
        if ($v === '' && $col === 'content' && isset($entity->body)) {
            $v = (string)$entity->body;
        } elseif ($v === '' && $col === 'body' && isset($entity->content)) {
            $v = (string)$entity->content;
        }

        return $v;
    }

    /**
     * エンティティに本文テキストを設定する。
     *
     * - Messages テーブルのスキーマを確認し、存在するカラム（content または body）へ文字列を代入。
     * - 両方存在しない場合はフォールバックとして content に格納。
     *
     * @param \Cake\Datasource\EntityInterface|object $entity 対象エンティティ
     * @param string $text 設定する本文テキスト
     * @return void
     */
    private function writeText($entity, string $text): void
    {
        $Messages = $this->fetchTable('Messages');
        $schema = $Messages->getSchema();

        if ($schema->hasColumn('content')) {
            $entity->set('content', $text);
        }
        if ($schema->hasColumn('body')) {
            $entity->set('body', $text);
        }
        // どちらも無いケースは想定外だが、念のため content に入れる
        if (!$schema->hasColumn('content') && !$schema->hasColumn('body')) {
            $entity->set('content', $text);
        }
    }

    /**
     * 指定された会話(conversation)に属するメッセージ一覧を取得するAPI。
     *
     * 主な処理内容:
     * - GET メソッドのみ許可。
     * - 認証済みユーザーIDを取得（未認証時は401を返す）。
     * - クエリパラメータ:
     *     - `conversation_id` : 取得対象の会話ID（必須）
     *     - `before_id`       : 指定IDより前のメッセージのみ取得（オプション）
     *     - `limit`           : 取得件数（1〜100、デフォルト30）
     * - 対象ユーザーが参加している会話であることを確認。
     * - メッセージ本文（content / body）を取得し、空文字は除外。
     * - 送信者が自分かどうかを `fromMe` で判定。
     * - 作成日時は ISO8601 形式 (`Y-m-d\TH:i:sP`) で返却。
     *
     * レスポンス構造:
     * ```json
     * {
     *   "messages": [
     *     {
     *       "id": 123,
     *       "fromMe": true,
     *       "text": "こんにちは",
     *       "created": "2025-10-28T12:34:56+09:00"
     *     }
     *   ],
     *   "paging": {
     *     "has_more": true,
     *     "next_before_id": 101
     *   }
     * }
     * ```
     *
     * @return \Cake\Http\Response|null JSON形式のメッセージ一覧またはエラーレスポンス
     * @throws \Cake\Http\Exception\NotFoundException 対象の会話が存在しない場合
     */
    public function index()
    {
        $this->request->allowMethod(['get']);
        $uid = $this->requireUserId();
        if ($uid instanceof \Cake\Http\Response) {
            return $uid;
        }

        $conversationId = (int)$this->request->getQuery('conversation_id');
        $beforeId = (int)$this->request->getQuery('before_id', 0);
        $limit = min(100, max(1, (int)$this->request->getQuery('limit', 30)));
        if ($conversationId <= 0) {
            return $this->json400('conversation_id は必須です');
        }

        $Conversations = $this->fetchTable('Conversations');
        $conv = $Conversations->find()
            ->where(['id' => $conversationId])
            ->where($this->participantCondition($uid))
            ->first();
        if (!$conv) {
            throw new NotFoundException('Conversation not found');
        }

        $Messages = $this->fetchTable('Messages');
        $q = $Messages->find()->where(['conversation_id' => $conversationId]);
        if ($beforeId > 0) {
            $q->andWhere(['Messages.id <' => $beforeId]);
        }
        $q->order(['Messages.id' => 'DESC'])->limit($limit);

        $rows = [];
        foreach ($q as $m) {
            $text = trim($this->readText($m));
            if ($text === '') {
                continue;
            }

            $senderRefId = (int)($m->sender_ref_id ?? 0);
            $senderIdCompat = (int)($m->sender_id ?? 0);

            $rows[] = [
                'id' => (int)$m->id,
                'fromMe' => ($senderRefId > 0 ? $senderRefId : $senderIdCompat) === (int)$uid,
                'text' => $text,
                'created' => $m->created ? $m->created->format('c') : null,
            ];
        }
        $rows = array_reverse($rows);

        $this->set([
            'messages' => $rows,
            'paging' => [
                'has_more' => count($rows) >= $limit,
                'next_before_id' => $rows ? $rows[0]['id'] : null,
            ],
        ]);
        $this->viewBuilder()->setOption('serialize', ['messages', 'paging']);
    }

    /**
     * メッセージ送信API。
     *
     * 主な処理内容:
     * - POSTメソッドのみ許可。
     * - 認証済みユーザーIDを取得（未認証時は401を返す）。
     * - リクエストデータ:
     *     - `conversation_id` : 送信先の会話ID（必須）
     *     - `text`            : メッセージ本文（必須）
     * - 対象ユーザーが会話に参加していることを確認。
     * - Messages テーブルに新規メッセージを登録し、
     *   Conversations テーブルの `last_message_id` / `last_message_at` を更新。
     * - content / body 両スキーマに対応し、旧フィールド(sender_id等)も互換サポート。
     * - トランザクション内で保存処理を実行し、整合性を保証。
     *
     * レスポンス構造（JSON）:
     * ```json
     * {
     *   "message": {
     *     "id": 123,
     *     "fromMe": true,
     *     "text": "こんにちは",
     *     "created": "2025-10-28T12:34:56+09:00"
     *   }
     * }
     * ```
     *
     * @return \Cake\Http\Response|null JSON形式の作成結果またはエラーレスポンス
     * @throws \Cake\Http\Exception\NotFoundException 対象の会話が存在しない場合
     */
    public function send()
    {
        $this->request->allowMethod(['post']);
        $uid = $this->requireUserId();
        if ($uid instanceof \Cake\Http\Response) {
            return $uid;
        }

        $data = $this->request->getData();
        $conversationId = (int)($data['conversation_id'] ?? 0);
        $text = trim((string)($data['text'] ?? ''));
        if ($conversationId <= 0 || $text === '') {
            return $this->json400('conversation_id と text は必須です');
        }

        $Conversations = $this->fetchTable('Conversations');
        $conv = $Conversations->find()
            ->where(['id' => $conversationId])
            ->where($this->participantCondition($uid))
            ->first();
        if (!$conv) {
            throw new NotFoundException('Conversation not found');
        }

        $Messages = $this->fetchTable('Messages');
        $schema = $Messages->getSchema();

        $msg = $Messages->newEmptyEntity();

        // 必須外部キー
        $msg->set('conversation_id', $conversationId);

        // 多態スキーマに対応（スキーマを見て確実にセット）
        if ($schema->hasColumn('sender_type')) {
            $msg->set('sender_type', 'user');
        }

        if ($schema->hasColumn('sender_ref_id')) {
            $msg->set('sender_ref_id', $uid);
        }

        // 旧スキーマ互換
        if ($schema->hasColumn('sender_id')) {
            $msg->set('sender_id', $uid);
        }

        // テキスト（content/body どちらでも）
        $this->writeText($msg, $text);

        $Messages->getConnection()->transactional(function () use ($Messages, $Conversations, $msg, $conv) {
            $Messages->saveOrFail($msg);

            // 会話の last_* 更新（存在する場合のみ）
            if ($conv->has('last_message_id') || property_exists($conv, 'last_message_id')) {
                $conv->set('last_message_id', $msg->id);
            }
            if ($conv->has('last_message_at') || property_exists($conv, 'last_message_at')) {
                $conv->set('last_message_at', $msg->created);
            }
            $Conversations->saveOrFail($conv);
        });

        $this->set([
            'message' => [
                'id' => (int)$msg->id,
                'fromMe' => true,
                'text' => $text,
                'created' => $msg->created ? $msg->created->format('c') : null,
            ],
        ]);
        $this->viewBuilder()->setOption('serialize', ['message']);
    }
}
