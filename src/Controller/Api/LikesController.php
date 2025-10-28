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
     * コントローラ初期化
     *
     * - JSONを直接返すため View を使わない
     * - 必要なテーブルをロード
     * - Authentication が未ロードでも安全にロード
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

        // ▼開発中に未ログインでも favorites を通したい場合だけ有効化
        // $this->Authentication->allowUnauthenticated(['favorites']);
    }

    /**
     * すべてのアクションを JSON で返す
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->response = $this->response->withType('application/json; charset=utf-8');
    }

    /**
     * GET /api/likes/favorites.json
     * 自分が「いいね」したポートフォリオ一覧（JWT必須）
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

            $items = [];
            foreach ($rows as $p) {
                // 念のため Users が無いケースをガード
                $u = $p->user ?? null;

                $items[] = [
                    'id' => (int)$p->id,
                    'title' => (string)($p->title ?? ''),
                    'like_count' => $this->Likes->find()->where(['portfolio_id' => $p->id])->count(),
                    'liked_by_me' => true, // 自分の「お気に入り」なので常に true
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
     * POST /api/likes/toggle.json
     * いいねのON/OFF（JWT必須）
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
     * 相対パスを絶対URLへ補正
     * - /icons/*   → /img/icons/* に寄せる
     * - /img/uploads/* → /uploads/* に寄せる（互換）
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

        // 先頭スラッシュを付与
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

    /** JSON 200 */
    private function jsonOk(array $data, int $status = 200)
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $this->response
            ->withType('application/json; charset=utf-8')
            ->withStatus($status)
            ->withStringBody($json);
    }

    /** JSON エラー */
    private function jsonError(string $message, int $status = 400)
    {
        $json = json_encode(['error' => $message, 'status' => $status], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $this->response
            ->withType('application/json; charset=utf-8')
            ->withStatus($status)
            ->withStringBody($json);
    }
}
