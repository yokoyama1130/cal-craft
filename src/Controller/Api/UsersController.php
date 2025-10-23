<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Event\EventInterface;
use Cake\Routing\Router;
use Cake\Utility\Text;
use Firebase\JWT\JWT;

class UsersController extends AppController
{
    /**
     * コントローラ初期化。
     * - `Users`/`Follows`/`Portfolios` テーブルを取得してプロパティに設定。
     * - JSON ビュークラスを使用。
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->Users = $this->fetchTable('Users');
        $this->Follows = $this->fetchTable('Follows');
        $this->Portfolios = $this->fetchTable('Portfolios');
        $this->Users = $this->fetchTable('Users');

        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * 各アクション実行前のフィルタ。
     * - 認証コンポーネントがある場合、`register`,`login`,`view`,
     *   `resendVerification`,`verifyEmail` を未認証で許可。
     *
     * @param \Cake\Event\EventInterface $event イベント
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        if ($this->components()->has('Authentication')) {
            $this->Authentication->allowUnauthenticated(['register','login','view','resendVerification','verifyEmail', 'search']);
        }
    }

    /**
     * GET /api/users/search.json
     * ユーザー検索API。名前部分一致で最大50件を返す。
     * - クエリパラメータ `q` が空なら最新10件を返す。
     * - 返却: id, name, bio, icon_url。
     *
     * @return \Cake\Http\Response|null|void JSON をシリアライズして返却
     */
    public function search()
    {
        $this->request->allowMethod(['get']);

        $q = (string)$this->request->getQuery('q', '');

        // 必要な列だけを明示
        $query = $this->Users->find()
            ->select(['id', 'name', 'bio', 'icon_path']);

        if ($q !== '') {
            $query->where(['Users.name LIKE' => '%' . $q . '%']);
        }

        // ★ created が無い環境でも安全な並び替え（id DESC）
        $query->order(['Users.id' => 'DESC']);

        // キーワード無しの時だけ件数を絞る
        if ($q === '') {
            $query->limit(10);
        } else {
            $query->limit(50);
        }

        $items = [];
        foreach ($query as $u) {
            // 例: 'icons/xxx.png' → '/img/icons/xxx.png'
            $icon = (string)($u->icon_path ?? '');
            if ($icon !== '' && strpos($icon, 'icons/') === 0) {
                $icon = '/img/' . ltrim($icon, '/'); // => /img/icons/xxx.png
            }
            $items[] = [
                'id' => (int)$u->id,
                'name' => (string)($u->name ?? ''),
                'bio' => (string)($u->bio ?? ''),
                'icon_url' => $icon,
            ];
        }

        $this->set(['success' => true, 'items' => $items]);
        $this->viewBuilder()->setOption('serialize', ['success', 'items']);
    }

    // ---------------- Auth ----------------

    /**
     * POST /api/users/login.json
     * 認証API。email/password を検証し、成功時に JWT を返す。
     * - 入力: {email, password}（JSON もしくは form）
     * - 401: 資格情報不正 / 403: メール未認証 / 422: 必須不足
     * - 200: { success, token, user:{id,name,email} }
     *
     * @return \Cake\Http\Response|null|void JSON をシリアライズして返却
     */
    public function login()
    {
        $this->request->allowMethod(['post']);

        $data = json_decode((string)$this->request->getBody(), true) ?? $this->request->getData();
        $email = (string)($data['email'] ?? '');
        $password = (string)($data['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->response = $this->response->withStatus(422);
            $this->set(['success' => false, 'message' => 'email と password は必須です', '_serialize' => ['success', 'message']]);

            return;
        }

        $user = $this->Users->find()
            ->select(['id','name','email','password','email_verified'])
            ->where(['email' => $email])
            ->first();

        if (!$user || !(new DefaultPasswordHasher())->check($password, (string)$user->password)) {
            $this->response = $this->response->withStatus(401);
            $this->set(['success' => false, 'message' => 'メールアドレスまたはパスワードが不正です', '_serialize' => ['success', 'message']]);

            return;
        }
        if (!(bool)$user->email_verified) {
            $this->response = $this->response->withStatus(403);
            $this->set(['success' => false, 'message' => 'メール認証が未完了です。メール内リンクから認証してください。', '_serialize' => ['success','message']]);

            return;
        }

        $token = $this->generateJwt((int)$user->id);

        $this->set([
            'success' => true,
            'token' => $token,
            'user' => ['id' => (int)$user->id, 'name' => (string)$user->name, 'email' => (string)$user->email],
            '_serialize' => ['success','token','user'],
        ]);
    }

    /**
     * POST /api/users/register.json
     * 新規ユーザー登録。エンティティを作成し保存後、メール認証用トークンを発行し確認メールを送信。
     * - 入力: ユーザー情報（JSON または form）。`sns_links` は初期JSONを付与。
     * - 成功: 200 `{ success, message, user_id }`
     * - 失敗: 422 `{ success:false, errors }`
     *
     * @return \Cake\Http\Response|null|void JSON をシリアライズして返却
     */
    public function register()
    {
        $this->request->allowMethod(['post']);

        $user = $this->Users->newEmptyEntity();
        $data = json_decode((string)$this->request->getBody(), true) ?? $this->request->getData();

        $data['sns_links'] = json_encode(['twitter' => '', 'github' => '', 'youtube' => '', 'instagram' => '']);

        $user = $this->Users->patchEntity($user, $data);
        $user->email_verified = false;
        $user->email_token = Text::uuid();

        if ($this->Users->save($user)) {
            try {
                $mailer = new \Cake\Mailer\Mailer('default');
                $mailer->setTo($user->email)
                    ->setSubject('【OrcaFolio】メール認証のお願い')
                    ->deliver(
                        "以下のURLをクリックしてメール認証を完了してください：\n\n" .
                        Router::url(['controller' => 'Users', 'action' => 'verifyEmail', $user->email_token, 'prefix' => false], true)
                    );
            } catch (\Throwable $e) {
                \Cake\Log\Log::warning('Mail send failed: ' . $e->getMessage());
            }

            $this->set(['success' => true, 'message' => '確認メールを送信しました。メールをご確認ください。' , 'user_id' => (int)$user->id, '_serialize' => ['success','message','user_id']]);

            return;
        }

        $this->response = $this->response->withStatus(422);
        $this->set(['success' => false, 'errors' => $user->getErrors(), '_serialize' => ['success','errors']]);
    }

    /**
     * POST /api/users/resendVerification.json
     * メール認証リンクの再送。email を受け取り、未認証ユーザーに新トークンを発行して送信。
     * - 422: email 未入力 / 404: ユーザーなし / 200: 送信完了 or 既に認証済み / 500: 送信失敗
     *
     * @return \Cake\Http\Response|null|void JSON をシリアライズして返却
     */
    public function resendVerification()
    {
        $this->request->allowMethod(['post']);
        $data = json_decode((string)$this->request->getBody(), true) ?? $this->request->getData();
        $email = (string)($data['email'] ?? '');
        if ($email === '') {
            $this->response = $this->response->withStatus(422);
            $this->set(['success' => false, 'message' => 'email は必須です', '_serialize' => ['success','message']]);

            return;
        }

        $user = $this->Users->find()->where(['email' => $email])->first();
        if (!$user) {
            $this->response = $this->response->withStatus(404);
            $this->set(['success' => false, 'message' => 'ユーザーが見つかりません', '_serialize' => ['success','message']]);

            return;
        }
        if ($user->email_verified) {
            $this->set(['success' => true, 'message' => 'すでに認証済みです', '_serialize' => ['success','message']]);

            return;
        }

        $user->email_token = Text::uuid();
        if ($this->Users->save($user)) {
            try {
                $mailer = new \Cake\Mailer\Mailer('default');
                $mailer->setTo($user->email)
                    ->setSubject('【OrcaFolio】メール認証の再送')
                    ->deliver(
                        "以下のURLから認証を完了してください：\n\n" .
                        Router::url(['controller' => 'Users', 'action' => 'verifyEmail', $user->email_token, 'prefix' => false], true)
                    );
            } catch (\Throwable $e) {
                \Cake\Log\Log::warning('Mail send failed: ' . $e->getMessage());
            }
            $this->set(['success' => true, 'message' => '認証メールを再送しました', '_serialize' => ['success','message']]);

            return;
        }

        $this->response = $this->response->withStatus(500);
        $this->set(['success' => false, 'message' => '再送に失敗しました', '_serialize' => ['success','message']]);
    }

    /**
     * GET /api/users/verifyEmail/{token}.json
     * メール認証処理。トークンでユーザーを特定し、`email_verified` を true に更新。
     * - 400: token 不足 / 404: 無効トークン / 500: 更新失敗 / 200: 認証完了
     *
     * @param string|null $token 認証トークン
     * @return \Cake\Http\Response|null|void JSON をシリアライズして返却
     */
    public function verifyEmail($token = null)
    {
        $this->request->allowMethod(['get']);
        if (!$token) {
            $this->response = $this->response->withStatus(400);
            $this->set(['success' => false, 'message' => 'token が必要です', '_serialize' => ['success','message']]);

            return;
        }
        $user = $this->Users->find()->where(['email_token' => $token])->first();
        if (!$user) {
            $this->response = $this->response->withStatus(404);
            $this->set(['success' => false, 'message' => '無効なトークンです', '_serialize' => ['success','message']]);

            return;
        }
        $user->email_verified = true;
        $user->email_token = null;
        if ($this->Users->save($user)) {
            $this->set(['success' => true, 'message' => 'メール認証が完了しました', '_serialize' => ['success','message']]);

            return;
        }
        $this->response = $this->response->withStatus(500);
        $this->set(['success' => false, 'message' => '認証に失敗しました', '_serialize' => ['success','message']]);
    }

    // ---------------- Profile ----------------

    /**
     * GET /api/users/profile.json
     * ログイン中ユーザーのプロフィールを返す（要認証）。
     * - 未認証は 401。認証済みは `setProfilePayload()` でレスポンス生成。
     *
     * @return \Cake\Http\Response|null|void
     */
    public function profile()
    {
        $this->request->allowMethod(['get']);

        $identity = $this->request->getAttribute('identity');
        if (!$identity) {
            $this->response = $this->response->withStatus(401);
            $this->set(['success' => false, 'message' => 'Unauthorized', '_serialize' => ['success','message']]);

            return;
        }

        $userId = (int)$identity->get('id');
        $this->setProfilePayload($userId, $userId);
    }

    /**
     * GET /api/users/view/{id}.json
     * 指定ユーザーのプロフィールを返す。閲覧者IDを付与して `setProfilePayload()` で整形。
     *
     * @param int $id 対象ユーザーID
     * @return \Cake\Http\Response|null|void
     */
    public function view($id)
    {
        $this->request->allowMethod(['get']);
        $authId = (int)($this->request->getAttribute('identity')->get('id') ?? 0);
        $this->setProfilePayload((int)$id, $authId);
    }

    /**
     * スキーマから候補カラム名を先頭から順に探して返す。
     * 見つからなければ null。
     *
     * @param \Cake\Database\Schema\TableSchemaInterface $schema テーブルスキーマ
     * @param string[] $candidates 候補カラム名の配列（優先順）
     * @return string|null 見つかったカラム名
     */
    private function pickColumn(\Cake\Database\Schema\TableSchemaInterface $schema, array $candidates): ?string
    {
        foreach ($candidates as $col) {
            if ($schema->getColumn($col) !== null) {
                return $col;
            }
        }

        return null;
    }

    /**
     * プロフィール応答ペイロードを組み立てて `set()` するヘルパ。
     * - Users/Follows/Portfolios を参照し、動的に作成日時・サムネ・いいね等を解決
     * - フォロー統計と閲覧者（$authId）視点の `is_following` を含む
     * - 最終的に JSON シリアライズ用の配列を `set()` する
     *
     * @param int $userId 対象ユーザーID
     * @param int $authId 閲覧者ユーザーID（未ログインは0）
     * @return void
     */
    private function setProfilePayload(int $userId, int $authId): void
    {
        // ---- Users 側（created / created_at 吸収）----
        $usersSchema = $this->Users->getSchema();
        $usersCreatedCol = $this->pickColumn($usersSchema, ['created','created_at']);

        $userSelect = ['id','name','email','bio','icon_path','sns_links'];
        if ($usersCreatedCol) {
            $userSelect[] = $usersCreatedCol;
        }

        $user = $this->Users->find()
            ->select($userSelect)
            ->where(['id' => $userId])
            ->first();

        if (!$user) {
            $this->response = $this->response->withStatus(404);
            $this->set(['success' => false, 'message' => 'User not found', '_serialize' => ['success','message']]);

            return;
        }

        // ---- フォロー統計 ----
        $followerCount = $this->Follows->find()->where(['followed_id' => $userId])->count();
        $followingCount = $this->Follows->find()->where(['follower_id' => $userId])->count();
        $isFollowing = $authId && $authId !== $userId
            ? $this->Follows->exists(['follower_id' => $authId, 'followed_id' => $userId])
            : false;

        // ---- Portfolios 側（created / thumbnail / likes を動的解決）----
        $portfolioSchema = $this->Portfolios->getSchema();
        $portfolioCreatedCol = $this->pickColumn($portfolioSchema, ['created','created_at']);
        $portfolioThumbCol = $this->pickColumn($portfolioSchema, [
            'thumbnail_path', 'image_path', 'image', 'thumbnail', 'cover_path', 'cover_url', 'photo_path',
        ]);
        $portfolioLikesCol = $this->pickColumn($portfolioSchema, ['likes','like_count','likes_count']);

        $portfolioSelect = ['id','user_id','title'];
        if ($portfolioThumbCol) {
            $portfolioSelect[] = $portfolioThumbCol;
        }

        if ($portfolioLikesCol) {
            $portfolioSelect[] = $portfolioLikesCol;
        }

        if ($portfolioCreatedCol) {
            $portfolioSelect[] = $portfolioCreatedCol;
        }

        $orderCol = $portfolioCreatedCol ?? 'id';

        $portfolios = $this->Portfolios->find()
            ->select($portfolioSelect)
            ->where(['user_id' => $userId /*, 'is_public' => true */])
            ->order([$orderCol => 'DESC'])
            ->limit(50)
            ->all()
            ->map(function ($p) use ($portfolioThumbCol, $portfolioLikesCol, $portfolioCreatedCol) {

                // 画像URLの整形（絶対URLならそのまま、相対なら /img/ を付けて絶対URL化）
                $imageUrl = null;
                if ($portfolioThumbCol && !empty($p->{$portfolioThumbCol})) {
                    $raw = (string)$p->{$portfolioThumbCol};
                    if (\preg_match('/^https?:\/\//i', $raw)) {
                        $imageUrl = $raw;
                    } else {
                        $imageUrl = \Cake\Routing\Router::url('/img/' . ltrim($raw, '/'), true);
                    }
                }

                // likes の取得（存在しなければ 0）
                $likes = 0;
                if ($portfolioLikesCol && isset($p->{$portfolioLikesCol})) {
                    $likes = (int)$p->{$portfolioLikesCol};
                }

                // created の整形（存在しなければ null）
                $createdStr = null;
                if ($portfolioCreatedCol && isset($p->{$portfolioCreatedCol})) {
                    $created = $p->{$portfolioCreatedCol};
                    // 型が FrozenTime/DateTime ならフォーマット、文字列ならそのまま
                    if (is_object($created) && method_exists($created, 'i18nFormat')) {
                        $createdStr = $created->i18nFormat('yyyy-MM-dd HH:mm:ss');
                    } elseif (is_string($created)) {
                        $createdStr = $created;
                    }
                }

                return [
                    'id' => (int)$p->id,
                    'title' => (string)$p->title,
                    'image_url' => $imageUrl,
                    'likes' => $likes,
                    'created' => $createdStr,
                ];
            })->toList();

        // ---- Users: 画像URL/SNS/created ----
        $iconUrl = !empty($user->icon_path)
            ? \Cake\Routing\Router::url('/img/' . ltrim((string)$user->icon_path, '/'), true)
            : null;

        $sns = [];
        try {
            $sns = json_decode((string)$user->sns_links, true) ?? [];
        } catch (\Throwable $e) {
        }

        $userCreated = null;
        if ($usersCreatedCol && isset($user->{$usersCreatedCol})) {
            $val = $user->{$usersCreatedCol};
            $userCreated = is_object($val) && method_exists($val, 'i18nFormat')
                ? $val->i18nFormat('yyyy-MM-dd HH:mm:ss')
                : (is_string($val) ? $val : null);
        }

        $payload = [
            'success' => true,
            'user' => [
                'id' => (int)$user->id,
                'name' => (string)$user->name,
                'bio' => (string)($user->bio ?? ''),
                'icon_url' => $iconUrl,
                'sns_links' => $sns,
                'created' => $userCreated,
            ],
            'stats' => [
                'followers' => $followerCount,
                'followings' => $followingCount,
                'is_following' => $isFollowing,
            ],
            'portfolios' => $portfolios,
        ];

        $this->set($payload + ['_serialize' => array_keys($payload)]);
    }

    // ---------------- Follow lists ----------------

    /**
     * GET /api/users/{id}/followers.json
     * 指定ユーザーのフォロワー一覧を取得し、id・name・icon_url を返す。
     *
     * @param int $id ユーザーID
     * @return \Cake\Http\Response|null|void JSON をシリアライズして返却
     */
    public function followers($id)
    {
        $this->request->allowMethod(['get']);

        $rows = $this->Follows->find()
            ->where(['followed_id' => (int)$id])
            ->contain(['Users' => function ($q) {
                return $q->select(['id', 'name', 'icon_path']);
            }])
            ->all()
            ->map(function ($f) {
                $u = $f->user;

                return [
                    'id' => (int)$u->id,
                    'name' => (string)$u->name,
                    'icon_url' => $u->icon_path ? Router::url('/img/' . ltrim((string)$u->icon_path, '/'), true) : null,
                ];
            })->toList();

        $this->set(['success' => true, 'users' => $rows, '_serialize' => ['success','users']]);
    }

    /**
     * GET /api/users/{id}/followers.json
     * 指定ユーザーのフォロワー一覧を返す（id, name, icon_url）。
     *
     * @param int $id ユーザーID
     * @return \Cake\Http\Response|null|void JSON をシリアライズして返却
     */
    public function followings($id)
    {
        $this->request->allowMethod(['get']);

        $rows = $this->Follows->find()
            ->where(['follower_id' => (int)$id])
            ->contain(['FollowedUsers' => function ($q) {
                return $q->select(['id','name','icon_path']);
            }])
            ->all()
            ->map(function ($f) {
                $u = $f->followed_user;

                return [
                    'id' => (int)$u->id,
                    'name' => (string)$u->name,
                    'icon_url' => $u->icon_path ? Router::url('/img/' . ltrim((string)$u->icon_path, '/'), true) : null,
                ];
            })->toList();

        $this->set(['success' => true, 'users' => $rows, '_serialize' => ['success','users']]);
    }

    // ---------------- Helpers ----------------

    /**
     * JWT トークンを生成するヘルパ。
     * - HS256 署名で7日間有効のトークンを発行。
     * - `JWT_SECRET` 環境変数がなければデフォルト文字列を使用。
     *
     * @param int $userId 対象ユーザーID
     * @return string 生成されたJWTトークン
     */
    private function generateJwt(int $userId): string
    {
        $secret = env('JWT_SECRET', 'dev-secret-change-me');
        $now = time();
        $exp = $now + 60 * 60 * 24 * 7; // 7日

        $payload = ['iss' => 'orcafolio', 'sub' => $userId, 'iat' => $now, 'exp' => $exp];

        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * POST /api/users/update.json
     * プロフィール更新API（要認証）。
     * - name / bio の更新、および `icon` ファイルのアップロード対応。
     * - 画像は `/webroot/img/icons/` に保存し、`icon_path` に相対パスを登録。
     * - 成功時: 更新後のユーザー情報を返却。
     * - 401: 未認証 / 422: 保存失敗。
     *
     * @return \Cake\Http\Response|null|void JSON をシリアライズして返却
     */
    public function update()
    {
        $this->request->allowMethod(['post','patch','put']);

        // 認証
        $identity = $this->request->getAttribute('identity');
        if (!$identity) {
            $this->response = $this->response->withStatus(401);
            $this->set(['success' => false, 'message' => 'Unauthorized', '_serialize' => ['success','message']]);

            return;
        }
        $userId = (int)$identity->get('id');

        $user = $this->Users->get($userId);

        // 通常フィールド
        $name = (string)($this->request->getData('name') ?? $user->name ?? '');
        $bio = (string)($this->request->getData('bio') ?? $user->bio ?? '');

        $data = ['name' => $name, 'bio' => $bio];

        // 画像アップロード（input name="icon"）
        $uploadedFiles = $this->request->getUploadedFiles();
        if (isset($uploadedFiles['icon'])) {
            $file = $uploadedFiles['icon'];
            if ($file && $file->getError() === UPLOAD_ERR_OK && $file->getSize() > 0) {
                $ext = pathinfo((string)$file->getClientFilename(), PATHINFO_EXTENSION) ?: 'jpg';
                $filename = time() . '_' . \Cake\Utility\Text::uuid() . '.' . $ext;

                $targetDir = WWW_ROOT . 'img' . DS . 'icons';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0775, true);
                }
                $file->moveTo($targetDir . DS . $filename);

                // DB保存用の相対パス
                $data['icon_path'] = 'icons/' . $filename;
            }
        }

        $user = $this->Users->patchEntity($user, $data);
        if ($this->Users->save($user)) {
            // レスポンス
            $iconUrl = !empty($user->icon_path)
                ? \Cake\Routing\Router::url('/img/' . ltrim((string)$user->icon_path, '/'), true)
                : null;

            $sns = [];
            try {
                $sns = json_decode((string)$user->sns_links, true) ?? [];
            } catch (\Throwable $e) {
            }

            $payload = [
                'success' => true,
                'message' => 'プロフィールを更新しました',
                'user' => [
                    'id' => (int)$user->id,
                    'name' => (string)$user->name,
                    'bio' => (string)($user->bio ?? ''),
                    'icon_url' => $iconUrl,
                    'sns_links' => $sns,
                ],
            ];
            $this->set($payload + ['_serialize' => array_keys($payload)]);

            return;
        }

        $this->response = $this->response->withStatus(422);
        $this->set(['success' => false, 'message' => '更新に失敗しました', 'errors' => $user->getErrors(), '_serialize' => ['success','message','errors']]);
    }
}
