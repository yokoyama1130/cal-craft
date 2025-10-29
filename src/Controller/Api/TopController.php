<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\Log\Log;

/**
 * Top API
 *
 * - GET /api/top/index.json
 *   公開ポートフォリオの最新10件を返す。
 *   認証済みなら `liked_by_me` を付与。
 *   サムネイル/ユーザーアイコンは絶対URLに補正。
 */
class TopController extends AppController
{
    /**
     * コントローラ初期化。
     * - `Portfolios` と `Likes` テーブルを読み込み。
     * - JSON ビュークラスを設定。
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->Portfolios = $this->fetchTable('Portfolios');
        $this->Likes = $this->fetchTable('Likes');
        $this->Users = $this->fetchTable('Users');
        $this->Follows = $this->fetchTable('Follows');

        $this->viewBuilder()->setClassName('Json');

        // Authentication が存在しても/しなくても落ちないようにロード可
        if (!$this->components()->has('Authentication')) {
            try {
                $this->loadComponent('Authentication');
            } catch (\Throwable $e) {
                // 認証を使わない環境でも動作するよう握りつぶし
                Log::warning('[TopController] Authentication load skipped: ' . $e->getMessage());
            }
        }
    }

    /**
     * 事前フィルタ
     *
     * - index は未ログインでもOK
     * - レスポンスはUTF-8 JSONに固定
     *
     * @param \Cake\Event\EventInterface $event イベント
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if ($this->components()->has('Authentication')) {
            $this->Authentication->allowUnauthenticated(['index']);
        }

        $this->response = $this->response->withType('application/json; charset=utf-8');
    }

    /**
     * GET /api/top/index.json
     *
     * - 公開ポートフォリオ最新10件
     * - like_count を集計
     * - 認証済みなら liked_by_me を一括判定（N+1回避）
     * - thumbnail / user.icon_path を絶対URLに補正
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->request->allowMethod(['get']);

        try {
            // 1) 公開ポートフォリオを取得（Usersも最小列で）
            $rows = $this->Portfolios->find()
                ->select(['id', 'title', 'description', 'thumbnail', 'user_id', 'created'])
                ->contain([
                    'Users' => function ($q) {
                        return $q->select(['id', 'name', 'icon_path']);
                    },
                ])
                ->where(['Portfolios.is_public' => true])
                ->order(['Portfolios.created' => 'DESC'])
                ->limit(10)
                ->all()
                ->toArray();

            if (!$rows) {
                $this->set(['success' => true, 'portfolios' => []]);
                $this->viewBuilder()->setOption('serialize', ['success', 'portfolios']);

                return;
            }

            // 2) 一括集計用にIDを抽出
            $portfolioIds = array_map(fn($p) => (int)$p->id, $rows);
            $authorUserIds = [];
            foreach ($rows as $p) {
                if (!empty($p->user_id)) {
                    $authorUserIds[(int)$p->user_id] = true;
                }
            }

            // 3) like_count をまとめて取得
            $fn = $this->Likes->find()->func();
            $likeCounts = $this->Likes->find()
                ->select([
                    'portfolio_id',
                    'cnt' => $fn->count('*'),
                ])
                ->where(['portfolio_id IN' => $portfolioIds])
                ->group('portfolio_id')
                ->enableHydration(false)
                ->all()
                ->combine('portfolio_id', 'cnt')
                ->toArray();

            // 4) liked_by_me / is_followed_by_me の下ごしらえ
            $identity = $this->request->getAttribute('identity');
            $meId = $identity ? (int)$identity->get('id') : 0;

            // liked_by_me（一括）: 自分が「いいね」した portfolio_id をマップ化
            $likedMap = [];
            if ($meId > 0) {
                $likedMap = $this->Likes->find()
                    ->select(['portfolio_id'])
                    ->where([
                        'user_id' => $meId, // 会社アカウントも扱うならここを拡張
                        'portfolio_id IN' => $portfolioIds,
                    ])
                    ->enableHydration(false)
                    ->all()
                    ->combine('portfolio_id', fn() => true)
                    ->toArray();
            }

            // is_followed_by_me（一括）: 自分(meId)→投稿者(author_user_id) のフォロー有無
            $followMap = [];
            if ($meId > 0 && $authorUserIds) {
                $q = $this->Follows->find()
                    ->select(['followed_id'])
                    ->where([
                        'follower_id' => $meId,
                        'followed_id IN' => array_keys($authorUserIds),
                    ])
                    ->enableHydration(false)
                    ->all();
                foreach ($q as $r) {
                    $followMap[(int)$r['followed_id']] = true;
                }
            }

            // 5) レスポンス整形（サムネ/アイコンを絶対URL化）
            $out = [];
            foreach ($rows as $p) {
                // サムネ補正 → 絶対URL
                $thumb = (string)($p->thumbnail ?? '');
                $thumb = $this->normalizePublicPath($thumb);
                $thumbAbs = $this->absUrl($thumb);

                // 投稿者
                $authorId = (int)$p->user_id;
                $userArr = null;
                if ($p->user) {
                    $icon = (string)($p->user->icon_path ?? '');
                    $icon = $this->normalizePublicPath($icon, forIcon: true);
                    $iconAbs = $this->absUrl($icon);

                    $userArr = [
                        'id' => (int)$p->user->id,
                        'name' => (string)($p->user->name ?? 'User'),
                        'icon_path' => $iconAbs,
                        'is_followed_by_me' => (bool)($followMap[(int)$p->user->id] ?? false), // ★ 追加
                    ];
                }

                $out[] = [
                    'id' => (int)$p->id,
                    'title' => (string)($p->title ?? ''),
                    'description' => (string)($p->description ?? ''),
                    'thumbnail' => $thumbAbs,
                    'like_count' => (int)($likeCounts[(int)$p->id] ?? 0),
                    'liked_by_me' => (bool)($likedMap[(int)$p->id] ?? false),
                    'author_user_id' => $authorId, // ★ 追加（クライアントで follow API を叩くため）
                    'is_followed_by_me' => (bool)($followMap[$authorId] ?? false), // ★ トップ直下にも置いておく
                    'user' => $userArr,
                ];
            }

            $this->set(['success' => true, 'portfolios' => $out]);
            $this->viewBuilder()->setOption('serialize', ['success', 'portfolios']);
        } catch (\Throwable $e) {
            Log::error('[api.top.index] ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            $this->response = $this->response->withStatus(500);
            $this->set(['success' => false, 'message' => 'internal_error']);
            $this->viewBuilder()->setOption('serialize', ['success', 'message']);
        }
    }

    // ─────────────── helpers ───────────────

    /**
     * 公開パスを正規化する。
     *
     * @param string $path 対象パス
     * @param bool $forIcon アイコン用補正を行う場合はtrue
     * @return string 正規化済みパス
     */
    private function normalizePublicPath(string $path, bool $forIcon = false): string
    {
        $p = trim($path);
        if ($p === '') {
            return $p;
        }

        // 既に http(s) は untouched（absUrl 側で最終整形）
        if (preg_match('#^https?://#i', $p)) {
            return $p;
        }

        // 先頭スラッシュ付与
        if ($p[0] !== '/') {
            $p = '/' . $p;
        }

        // /img/uploads → /uploads
        if (strpos($p, '/img/uploads/') === 0) {
            $p = substr($p, strlen('/img')); // => /uploads/...
        }

        // /icons/* → /img/icons/* へ寄せる（実体が /img/icons の場合）
        if ($forIcon && strpos($p, '/icons/') === 0 && strpos($p, '/img/icons/') !== 0) {
            $p = '/img' . $p;
        }

        // 二重スラッシュ抑制
        $p = preg_replace('#/{2,}#', '/', $p);

        return $p;
    }

    /**
     * パスまたはURLを絶対URLに整形する。
     *
     * @param string|null $path 相対パスまたはURL
     * @return string|null 整形済みの絶対URL（無効な場合はnull）
     */
    private function absUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        // 既に http(s)
        if (preg_match('#^https?://#i', $path)) {
            $u = parse_url($path);
            if (!$u) {
                return $path;
            }

            $scheme = $u['scheme'] ?? 'http';
            $host = ($u['host'] ?? 'localhost') === 'localhost' ? '127.0.0.1' : ($u['host'] ?? 'localhost');
            $port = isset($u['port']) ? ':' . $u['port'] : '';
            $p = $this->normalizePublicPath($u['path'] ?? '');
            $query = isset($u['query']) ? '?' . $u['query'] : '';

            return $scheme . '://' . $host . $port . $p . $query;
        }

        // 相対 → 絶対
        $p = $this->normalizePublicPath($path);

        $uri = $this->request->getUri();
        $scheme = $uri->getScheme() ?: 'http';
        $host = $uri->getHost() ?: 'localhost';
        if ($host === 'localhost') {
            $host = '127.0.0.1'; // iOSシミュ対策
        }
        $port = $uri->getPort();
        $base = $scheme . '://' . $host . ($port ? ':' . $port : '');

        return $base . $p;
    }
}
