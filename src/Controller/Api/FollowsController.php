<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\Event\EventInterface;

class FollowsController extends AppController
{
    /**
     * initialize
     *
     * API用コントローラの初期化処理を行います。
     * - JSON固定レスポンスのために RequestHandler を読み込み、View描画を無効化（autoRender=false）
     * - 使用するテーブル（Follows, Users）を取得
     * - Authentication コンポーネントが未ロードでも致命的にならないよう防御的にロード
     * - 未認証でも閲覧可能なアクション（followings, followers）を許可
     *
     * 注意:
     * - ここでは JSON を直接書き込む実装（withStringBody等）を前提にしているため、
     *   JsonView/serialize は使用しません。
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        // 直接 JSON を書き込む方式にするので View は使わない
        $this->loadComponent('RequestHandler');
        $this->autoRender = false;

        // モデル
        $this->Follows = $this->fetchTable('Follows');
        $this->Users = $this->fetchTable('Users');

        // Authentication が未ロードでも落ちないように
        if (!$this->components()->has('Authentication')) {
            $this->loadComponent('Authentication');
        }
        if ($this->components()->has('Authentication')) {
            $this->Authentication->allowUnauthenticated(['followings', 'followers']);
        }
    }

    /**
     * beforeFilter
     *
     * 各アクション実行前に共通で実行される前処理。
     * - APIレスポンスを常に JSON 形式（UTF-8）に統一します。
     * - HTMLやViewを返す通常のWebアプリとは異なり、すべてJSONレスポンスを想定しています。
     *
     * @param \Cake\Event\EventInterface $event イベントオブジェクト
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->response = $this->response->withType('application/json; charset=utf-8');
    }

    /**
     * followings
     *
     * 指定したユーザーが「フォローしているユーザー一覧」を取得し、JSON形式で返します。
     *
     * エンドポイント例:
     * ```
     * GET /api/follows/followings/:userId.json
     * ```
     *
     * 処理内容:
     * 1. GETメソッドのみ許可。
     * 2. `$userId` が数値でない、または 0 以下の場合は 400 を返す。
     * 3. `follower_id = $userId` のレコードを検索し、`FollowedUsers` 関連を含めて取得。
     * 4. 各フォロー先ユーザーの基本情報（id, name, icon_path）を整形して配列化。
     * 5. ログイン中のユーザーが存在する場合、自分がそのユーザーをフォローしているかを
     *    `is_followed_by_me` として付与。
     * 6. 結果を `{"followings": [...users...]}` の形式で JSON レスポンスとして返す。
     *
     * @param int|null $userId フォロー一覧を取得するユーザーのID
     * @return \Cake\Http\Response JSONレスポンス
     */
    public function followings($userId = null)
    {
        try {
            $this->request->allowMethod(['get']);

            $id = is_numeric($userId) ? (int)$userId : 0;
            if ($id <= 0) {
                return $this->jsonError('user_id は必須です', 400);
            }

            $meId = $this->getLoginUserId();

            $rows = $this->Follows->find()
                ->where(['follower_id' => $id])
                ->contain(['FollowedUsers' => fn($q) => $q->select(['id','name','icon_path'])])
                ->order(['Follows.id' => 'DESC'])
                ->all();

            $result = [];
            $targetIds = [];
            foreach ($rows as $f) {
                if (!$f->followed_user) {
                    continue;
                }
                $result[] = [
                    'id' => (int)$f->id,
                    'user' => [
                        'id' => (int)$f->followed_user->id,
                        'name' => (string)$f->followed_user->name,
                        'icon_path' => $this->absUrl($f->followed_user->icon_path),
                    ],
                ];
                $targetIds[] = (int)$f->followed_user->id;
            }

            if ($meId && $targetIds) {
                $map = $this->followMap($meId, $targetIds);
                foreach ($result as &$row) {
                    $row['is_followed_by_me'] = (bool)($map[$row['user']['id']] ?? false);
                }
                unset($row);
            }

            return $this->jsonOk(['followings' => $result]);
        } catch (\Throwable $e) {
            $this->log('[api.followings] ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'error');

            return $this->jsonError('internal_error', 500);
        }
    }

    /**
     * followers
     *
     * 指定したユーザーを「フォローしているユーザー一覧」を取得し、JSON形式で返します。
     *
     * エンドポイント例:
     * ```
     * GET /api/follows/followers/:userId.json
     * ```
     *
     * 処理内容:
     * 1. GETメソッドのみ許可。
     * 2. `$userId` が数値でない、または 0 以下の場合は 400 を返す。
     * 3. `followed_id = $userId` のレコードを検索し、`FollowerUsers` 関連を含めて取得。
     * 4. 各フォロワーユーザーの基本情報（id, name, icon_path）を整形して配列化。
     * 5. ログイン中のユーザーが存在する場合、自分がそのユーザーをフォローしているかを
     *    `is_followed_by_me` として付与。
     * 6. 結果を `{"followers": [...users...]}` の形式で JSON レスポンスとして返す。
     *
     * 備考:
     * - `FollowerUsers` は FollowsTable の belongsTo 関連定義で、
     *   `foreignKey` = follower_id を参照する必要があります。
     * - 返却されるアイコンパスは `absUrl()` により絶対URLに変換されます。
     *
     * @param int|null $userId フォロワー一覧を取得する対象ユーザーのID
     * @return \Cake\Http\Response JSONレスポンス
     */
    public function followers($userId = null)
    {
        try {
            $this->request->allowMethod(['get']);

            $id = is_numeric($userId) ? (int)$userId : 0;
            if ($id <= 0) {
                return $this->jsonError('user_id は必須です', 400);
            }

            $meId = $this->getLoginUserId();

            $rows = $this->Follows->find()
                ->where(['followed_id' => $id])
                ->contain(['FollowerUsers' => fn($q) => $q->select(['id','name','icon_path'])])
                ->order(['Follows.id' => 'DESC'])
                ->all();

            $result = [];
            $targetIds = [];
            foreach ($rows as $f) {
                if (!$f->follower_user) {
                    continue;
                }
                $result[] = [
                    'id' => (int)$f->id,
                    'user' => [
                        'id' => (int)$f->follower_user->id,
                        'name' => (string)$f->follower_user->name,
                        'icon_path' => $this->absUrl($f->follower_user->icon_path),
                    ],
                ];
                $targetIds[] = (int)$f->follower_user->id;
            }

            if ($meId && $targetIds) {
                $map = $this->followMap($meId, $targetIds);
                foreach ($result as &$row) {
                    $row['is_followed_by_me'] = (bool)($map[$row['user']['id']] ?? false);
                }
                unset($row);
            }

            return $this->jsonOk(['followers' => $result]);
        } catch (\Throwable $e) {
            $this->log('[api.followers] ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'error');

            return $this->jsonError('internal_error', 500);
        }
    }

    // ----- helpers -----

    /**
     * getLoginUserId
     *
     * 現在のリクエストに紐づくログイン中ユーザーのIDを取得します。
     *
     * 処理内容:
     * - Authenticationプラグインによってセットされた `$request->getAttribute('identity')`
     *   からユーザー情報を取得。
     * - identityが存在しない場合は `null` を返す。
     * - IDが数値であれば整数値にキャストして返す。
     * - 数値でない場合（不正データなど）は `null` を返す。
     *
     * 例:
     * ```php
     * $userId = $this->getLoginUserId();
     * if ($userId) {
     *     // ログイン中のユーザーIDが取得できた
     * }
     * ```
     *
     * @return int|null ログイン中のユーザーID。未ログイン時は null。
     */
    private function getLoginUserId(): ?int
    {
        $identity = $this->request->getAttribute('identity');
        if (!$identity) {
            return null;
        }
        $id = $identity->get('id');

        return is_numeric($id) ? (int)$id : null;
    }

    /**
     * followMap
     *
     * 処理内容:
     * 1. `$targetIds` の重複を除去し、すべて整数にキャスト。
     * 2. `$meId` がフォローしているユーザーの `followed_id` を取得。
     * 3. 結果を `[followed_id => true]` の連想配列に整形して返す。
     *
     * 例:
     * ```php
     * // $meId = 10 のユーザーが [2, 5, 7] をフォローしているか確認
     * $map = $this->followMap(10, [2, 5, 7]);
     * // 結果: [2 => true, 7 => true] など
     * if (!empty($map[5])) {
     *     // ユーザー10はユーザー5をフォローしている
     * }
     * ```
     *
     * @param int $meId ログイン中のユーザーID
     * @param array $targetIds フォロー状態を確認するユーザーIDの配列
     * @return array フォロー関係のマップ [followed_id => true]
     */
    private function followMap(int $meId, array $targetIds): array
    {
        $targetIds = array_values(array_unique(array_map('intval', $targetIds)));
        if (!$targetIds) {
            return [];
        }

        $rows = $this->Follows->find()
            ->select(['followed_id'])
            ->where(['follower_id' => $meId, 'followed_id IN' => $targetIds])
            ->enableHydration(false)
            ->all();

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['followed_id']] = true;
        }

        return $map;
    }

    /**
     * absUrl
     *
     * 処理内容:
     * 1. `$path` が空または null の場合は null を返す。
     * 2. `http://` または `https://` で始まる場合は絶対URLとみなし、
     *    パス部分だけを正規化（`/icons` → `/img/icons` 等）して返す。
     * 3. 相対パスの場合は `normalizePublicPath()` を通して整形し、
     *    現在のホスト・スキーム・ポートを組み合わせて完全なURLを生成。
     *
     * 変換ルール（normalizePublicPath()で処理）:
     * - `/icons/...` → `/img/icons/...`
     * - `/img/uploads/...` → `/uploads/...`
     * - `img/icons/...` → `/img/icons/...`
     * - 先頭スラッシュがない場合は補完。
     * - 二重スラッシュは単一に統一。
     *
     * 使用例:
     * ```php
     * $this->absUrl('icons/user123.png');
     * // => http://127.0.0.1:8765/img/icons/user123.png
     * ```
     *
     * @param string|null $path 相対または絶対のファイルパス
     * @return string|null 絶対URL（または null）
     */
    private function absUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        $p = trim($path);

        // 既に絶対URLなら軽く整形して返す（/img/icons 互換も適用）
        if (preg_match('#^https?://#i', $p)) {
            try {
                $u = \Cake\Http\ServerRequestFactory::fromGlobals()->getUri()->withPath($p);
            } catch (\Throwable $e) {
                return $p;
            }
            // パス正規化だけやる
            $url = parse_url($p);
            $pathOnly = $url['path'] ?? '';
            $pathOnly = $this->normalizePublicPath($pathOnly);
            $host = $url['host'] ?? '';
            $scheme = $url['scheme'] ?? 'http';
            $port = isset($url['port']) ? ':' . $url['port'] : '';

            return $scheme . '://' . $host . $port . $pathOnly;
        }

        // 相対パス → 正規化
        $p = $this->normalizePublicPath($p);

        $uri = $this->request->getUri();
        $scheme = $uri->getScheme() ?: 'http';
        $host = $uri->getHost() ?: 'localhost';
        $port = $uri->getPort();
        $base = $scheme . '://' . $host . ($port ? ':' . $port : '');

        return $base . $p;
    }

    /**
     * normalizePublicPath
     *
     * @param string $raw 元の相対パスまたは絶対パス
     * @return string 正規化済みのパス（例：/img/icons/user.png）
     */
    private function normalizePublicPath(string $raw): string
    {
        $p = $raw;

        // 先頭スラッシュ付与
        if ($p === '' || $p[0] !== '/') {
            $p = '/' . $p;
        }

        // /img/uploads → /uploads（これまでの互換）
        if (strpos($p, '/img/uploads/') === 0) {
            $p = substr($p, strlen('/img'));// => /uploads/...
        }

        // /icons/* → /img/icons/*（今回の要件）
        if (preg_match('#^/icons/#', $p)) {
            $p = '/img' . $p; // => /img/icons/...
        }

        // img/icons/*（先頭にスラッシュが2重になっていないか一応整形）
        if (preg_match('#^/img/icons/#', $p) === 0 && preg_match('#^img/icons/#', ltrim($p, '/'))) {
            $p = '/' . ltrim($p, '/'); // 先頭に / を付ける
            if (strpos($p, '/img/icons/') !== 0) {
                $p = '/img/icons/' . ltrim($p, '/'); // 念のため
            }
        }

        // 二重スラッシュ抑制
        $p = preg_replace('#/{2,}#', '/', $p);

        return $p;
    }

    /**
     * jsonOk
     *
     * @param array $data レスポンスデータ
     * @param int $status HTTPステータスコード（省略時200）
     * @return \Cake\Http\Response JSON形式のHTTPレスポンス
     */
    private function jsonOk(array $data, int $status = 200)
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $this->response
            ->withType('application/json; charset=utf-8')
            ->withStatus($status)
            ->withStringBody($json);
    }

    /**
     * jsonError
     *
     * @param string $message エラーメッセージ
     * @param int $status HTTPステータスコード（デフォルト: 400）
     * @return \Cake\Http\Response JSON形式のHTTPレスポンス
     */
    private function jsonError(string $message, int $status = 400)
    {
        $payload = ['error' => $message, 'status' => $status];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $this->response
            ->withType('application/json; charset=utf-8')
            ->withStatus($status)
            ->withStringBody($json);
    }
}
