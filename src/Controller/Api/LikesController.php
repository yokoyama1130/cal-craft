<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\Log\Log;

/**
 * Likes API Controller
 *
 * - GET  /api/likes/favorites.json : 自分が「いいね」したポートフォリオ一覧（JWT必須）
 * - POST /api/likes/toggle.json    : いいねのON/OFF（JWT必須）
 */
class LikesController extends AppController
{
    /**
     * コントローラーの初期化処理
     *
     * - 親クラスの initialize() を呼び出し、共通初期化を継承。
     * - Authentication コンポーネントをロードし、認証機能を有効化。
     * - Likes テーブルを明示的にロードして、コントローラー内で利用可能にする。
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('RequestHandler');
        $this->autoRender = false;

        $this->Likes = $this->fetchTable('Likes');
        $this->Portfolios = $this->fetchTable('Portfolios');
        $this->Users = $this->fetchTable('Users');
        $this->Notifications = $this->fetchTable('Notifications');

        if (!$this->components()->has('Authentication')) {
            $this->loadComponent('Authentication');
        }
    }

    /**
     * 各アクション実行前の共通処理。
     *
     * - レスポンスの Content-Type を JSON (`application/json; charset=utf-8`) に設定する。
     * - 認証やアクセス制御などの前処理は parent::beforeFilter() に委譲。
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
     * ログインユーザーのお気に入り（いいね）ポートフォリオ一覧を取得するAPI。
     *
     * 主な処理内容:
     * - GETメソッドのみ許可。
     * - ログイン済みのユーザーIDを取得（未ログイン時は401を返す）。
     * - Likesテーブルから「自分がいいねした」ポートフォリオID一覧を取得。
     * - Portfoliosテーブルから対象IDの公開中ポートフォリオを取得し、
     *   投稿者(Users)情報を `contain()` で同時に取得。
     * - 各ポートフォリオの like_count をまとめて算出（N+1問題を回避）。
     * - サムネイル画像は複数カラム候補のうち、最初に存在するものを優先して採用。
     *
     * @return \Cake\Http\Response JSON形式のポートフォリオ一覧またはエラーレスポンス
     * @throws \Cake\Http\Exception\MethodNotAllowedException GET以外で呼び出された場合
     */
    public function favorites()
    {
        try {
            $this->request->allowMethod(['get']);

            $identity = $this->request->getAttribute('identity');
            if (!$identity) {
                return $this->jsonError('ログインが必要です', 401);
            }
            $userId = (int)$identity->get('id');
            if ($userId <= 0) {
                return $this->jsonError('ログインが必要です', 401);
            }

            // 自分が「いいね」したポートフォリオ ID の一覧
            $likedIds = $this->Likes->find()
                ->select(['portfolio_id'])
                ->where(['user_id' => $userId])
                ->andWhere(['portfolio_id IS NOT' => null])
                ->enableHydration(false)
                ->extract('portfolio_id')
                ->toList();

            if (!$likedIds) {
                return $this->jsonOk(['portfolios' => []]);
            }

            // 公開中のみ取得。ユーザー情報を同時に持ってくる
            $rows = $this->Portfolios->find()
                ->where([
                    'Portfolios.id IN' => $likedIds,
                    'Portfolios.is_public' => true,
                ])
                ->contain(['Users' => fn($q) => $q->select(['id', 'name', 'icon_path'])])
                ->order(['Portfolios.created' => 'DESC'])
                ->all();

            // まとめて like_count を取得（N+1回避：なくても動くが一応最適化）
            $likeCounts = [];
            $likeRows = $this->Likes->find()
                ->select(['portfolio_id', 'cnt' => 'COUNT(*)'])
                ->where(['portfolio_id IN' => $likedIds])
                ->group('portfolio_id')
                ->enableHydration(false)
                ->all();
            foreach ($likeRows as $r) {
                $likeCounts[(int)$r['portfolio_id']] = (int)$r['cnt'];
            }

            $items = [];
            foreach ($rows as $p) {
                $u = $p->user ?? null;

                // ▼ サムネ候補（存在するものを最初に拾う）
                $thumb = null;
                $candidates = [
                    $p->thumbnail ?? null,
                    $p->thumbnail_path ?? null,
                    $p->cover_image_path ?? null,
                    $p->image_path ?? null,
                    $p->image_url ?? null,
                    $p->imageUrl ?? null,
                    $p->img ?? null,
                ];
                foreach ($candidates as $c) {
                    if (!empty($c)) {
                        $thumb = $this->absUrl((string)$c);
                        break;
                    }
                }

                $items[] = [
                    'id' => (int)$p->id,
                    'title' => (string)($p->title ?? ''),
                    'like_count' => $likeCounts[(int)$p->id] ?? 0,
                    'liked_by_me' => true, // 自分の「お気に入り」なので常に true
                    'thumbnail' => $thumb, // ★ Flutter側はまずここを見る
                    'user' => $u ? [
                        'id' => (int)$u->id,
                        'name' => (string)($u->name ?? ''),
                        'icon_path' => $this->absUrl((string)($u->icon_path ?? '')),
                    ] : [
                        'id' => 0,
                        'name' => '',
                        'icon_path' => null,
                    ],
                ];
            }

            return $this->jsonOk(['portfolios' => $items]);
        } catch (\Throwable $e) {
            Log::error('[api.likes.favorites] ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            return $this->jsonError('internal_error', 500);
        }
    }

    /**
     * ポートフォリオの「いいね」状態をトグル（ON/OFF）するAPI。
     *
     * 主な処理内容:
     * - POSTメソッドのみ許可。
     * - ログイン済みユーザーIDを取得（未ログイン時は401を返す）。
     * - `portfolio_id` が指定されているか検証（不正時は400を返す）。
     * - すでに「いいね」済みの場合は削除し、未「いいね」なら新規作成。
     * - 最新の「いいね」数を集計して返却。
     *
     * @return \Cake\Http\Response JSON形式の結果レスポンス
     * @throws \Cake\Http\Exception\MethodNotAllowedException POST以外で呼び出された場合
     */
    public function toggle()
    {
        try {
            $this->request->allowMethod(['post']);

            $identity = $this->request->getAttribute('identity');
            if (!$identity) {
                return $this->jsonError('ログインが必要です', 401);
            }
            $userId = (int)$identity->get('id');
            if ($userId <= 0) {
                return $this->jsonError('ログインが必要です', 401);
            }

            $portfolioId = (int)$this->request->getData('portfolio_id');
            if ($portfolioId <= 0) {
                return $this->jsonError('portfolio_id が不正です', 400);
            }

            $existing = $this->Likes->find()
                ->where(['user_id' => $userId, 'portfolio_id' => $portfolioId])
                ->first();

            $liked = false;
            if ($existing) {
                $this->Likes->delete($existing);
            } else {
                $like = $this->Likes->newEntity([
                    'user_id' => $userId,
                    'portfolio_id' => $portfolioId,
                ]);
                if (!$this->Likes->save($like)) {
                    return $this->jsonError('保存に失敗しました', 422);
                }
                $liked = true;
            }

            $count = $this->Likes->find()->where(['portfolio_id' => $portfolioId])->count();

            return $this->jsonOk([
                'success' => true,
                'liked' => $liked,
                'likeCount' => $count,
            ]);
        } catch (\Throwable $e) {
            Log::error('[api.likes.toggle] ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            return $this->jsonError('internal_error', 500);
        }
    }

    // ───────────────────────── helpers ─────────────────────────

    /**
     * 相対パスを絶対URLに変換するユーティリティメソッド。
     *
     * @param string|null $path 相対または絶対パス
     * @return string|null 絶対URL（または入力が空ならnull）
     */
    private function absUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        $raw = trim($path);

        // 既に http(s) 完成形ならそのまま
        if (preg_match('#^https?://#i', $raw)) {
            return $raw;
        }

        // 先頭スラッシュ付与
        $p = '/' . ltrim($raw, '/');

        // /icons/* を /img/icons/* に寄せる
        if (strpos($p, '/icons/') === 0) {
            $p = '/img' . $p; // → /img/icons/...
        }
        // /img/uploads/* を /uploads/* に寄せる（過去データ互換）
        if (strpos($p, '/img/uploads/') === 0) {
            $p = substr($p, strlen('/img')); // → /uploads/...
        }

        // 二重スラッシュ抑制
        $p = preg_replace('#/{2,}#', '/', $p);

        // ベースURL
        $uri = $this->request->getUri();
        $scheme = $uri->getScheme() ?: 'http';
        $host = $uri->getHost() ?: 'localhost';
        $port = $uri->getPort();
        $base = $scheme . '://' . $host . ($port ? ':' . $port : '');

        return $base . $p;
    }

    /**
     * 成功レスポンス(JSON)を返す。
     *
     * @param array $data レスポンスデータ
     * @param int $status HTTPステータスコード（デフォルト: 200）
     * @return \Cake\Http\Response JSONレスポンス
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
     * エラーレスポンス(JSON)を返す。
     *
     * @param string $message エラーメッセージ
     * @param int $status HTTPステータスコード（デフォルト: 400）
     * @return \Cake\Http\Response JSONレスポンス
     */
    private function jsonError(string $message, int $status = 400)
    {
        $json = json_encode(
            ['error' => $message, 'status' => $status],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return $this->response
            ->withType('application/json; charset=utf-8')
            ->withStatus($status)
            ->withStringBody($json);
    }
}
