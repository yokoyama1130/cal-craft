<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\Utility\Text;
use Psr\Http\Message\UploadedFileInterface;

class PortfoliosController extends AppController
{
    /**
     * コントローラ初期化処理。
     *
     * - `Portfolios` テーブルを取得して `$this->Portfolios` にセットします。
     * - レンダラーとして JSON 用のビュービルダーを指定します（`Json` クラス）。
     *
     * CakePHP における各アクション実行前に 1 度だけ呼び出され、
     * コントローラ全体の共通セットアップを行います。
     *
     * @inheritDoc
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Portfolios = $this->fetchTable('Portfolios');
        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * 各アクション実行前のフィルタ処理。
     *
     * - 親クラスの `beforeFilter()` を呼び出して共通前処理を実行します。
     * - Authentication コンポーネントが有効な場合、
     *   `view` アクションのみ未ログイン（未認証）でもアクセス可能にします。
     *
     * 主にアクセス制御やリクエスト前の共通初期化を行うためのフックです。
     *
     * @param \Cake\Event\EventInterface $event ディスパッチャーが渡すイベントオブジェクト
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        // 詳細APIは未ログインでも閲覧可
        if ($this->components()->has('Authentication')) {
            $this->Authentication->allowUnauthenticated(['view', 'search']);
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
        $Portfolios = $this->Portfolios;
        $Likes = $this->fetchTable('Likes');

        $query = $Portfolios->find()
            ->contain(['Users' => function ($q) {
                return $q->select(['Users.id', 'Users.name', 'Users.icon_path']);
            }])
            ->where(['Portfolios.is_public' => true]);

        if ($q !== '') {
            $query->andWhere([
                'OR' => [
                    'Portfolios.title LIKE' => '%' . $q . '%',
                    'Portfolios.description LIKE' => '%' . $q . '%',
                ],
            ]);
        }

        $items = $query
            ->order(['Portfolios.created' => 'DESC'])
            ->limit(50)
            ->all()
            ->map(function ($p) use ($Likes) {
                // サムネ正規化（/img/uploads → /uploads へ寄せたい場合の例）
                $thumb = (string)($p->thumbnail ?? '');
                if (strpos($thumb, '/img/uploads/') === 0) {
                    $thumb = preg_replace('#^/img/uploads/#', '/uploads/', $thumb);
                }

                $likeCount = $Likes->find()->where(['portfolio_id' => $p->id])->count();

                return [
                    'id' => (int)$p->id,
                    'title' => (string)($p->title ?? ''),
                    'thumbnail' => $thumb,
                    'like_count' => (int)$likeCount,
                    'user' => $p->user ? [
                        'id' => (int)$p->user->id,
                        'name' => (string)($p->user->name ?? ''),
                        // Webは icon_path を img/icons 配下に置いている想定
                        'icon_url' => $p->user->icon_path ? '/img/' . ltrim($p->user->icon_path, '/') : '',
                    ] : null,
                    'created' => $p->created ? $p->created->format('c') : null,
                ];
            })
            ->toList();

        $this->set(['success' => true, 'items' => $items]);
        $this->viewBuilder()->setOption('serialize', ['success','items']);
    }

    /**
     * POST /api/portfolios/add.json
     *
     * @return \Cake\Http\Response|null|void JSON をシリアライズして返却。状況により早期 return。
     */
    public function add()
    {
        $this->request->allowMethod(['post']);

        $identity = $this->request->getAttribute('identity');
        if (!$identity) {
            $this->response = $this->response->withStatus(401);
            $this->set(['success' => false, 'message' => 'Unauthorized']);
            $this->viewBuilder()->setOption('serialize', ['success','message']);

            return;
        }

        Log::debug('[add] CT=' . $this->request->getHeaderLine('Content-Type'));

        $p = $this->Portfolios->newEmptyEntity();
        $p->user_id = (int)$identity->get('id');
        $p->company_id = null;

        $data = (array)$this->request->getData();

        // ---- サムネ（/webroot/uploads 配下に保存し、/uploads/xxx.jpg を返す）----
        $thumbnailFile = $this->request->getData('thumbnail_file');
        if (!$thumbnailFile instanceof UploadedFileInterface) {
            $uploaded = $this->request->getUploadedFiles();
            if (isset($uploaded['thumbnail_file']) && $uploaded['thumbnail_file'] instanceof UploadedFileInterface) {
                $thumbnailFile = $uploaded['thumbnail_file'];
            }
        }

        if ($thumbnailFile instanceof UploadedFileInterface && $thumbnailFile->getError() === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($thumbnailFile->getClientFilename(), PATHINFO_EXTENSION));
            $safeExt = in_array($ext, ['jpg','jpeg','png','webp'], true) ? $ext : 'jpg';
            $filename = Text::uuid() . '.' . $safeExt;

            $dir = WWW_ROOT . 'uploads' . DS;
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $thumbnailFile->moveTo($dir . $filename);

            $data['thumbnail'] = '/uploads/' . $filename; // ← ブラウザから参照する公開パス
        }

        if (empty($data['thumbnail'])) {
            $this->response = $this->response->withStatus(422);
            $this->set([
                'success' => false,
                'message' => 'サムネイル画像は必須です（multipart/form-data で thumbnail_file を送ってください）',
            ]);
            $this->viewBuilder()->setOption('serialize', ['success','message']);

            return;
        }

        // ---- TEXT NOT NULL を空文字で埋める（DB制約対策）----
        $textNotNull = [
            'link',
            'purpose','basic_spec','design_url','design_description','parts_list',
            'processing_method','processing_notes','analysis_method','analysis_result',
            'development_period','mechanical_notes','reference_links','tool_used','material_used',
            'github_url','experiment_summary',
        ];
        foreach ($textNotNull as $f) {
            if (!array_key_exists($f, $data) || $data[$f] === null) {
                $data[$f] = '';
            }
        }
        if (!isset($data['description'])) {
            $data['description'] = '';
        }

        // ---- 保存 ----
        $p = $this->Portfolios->patchEntity($p, $data);
        if (!$this->Portfolios->save($p)) {
            $this->response = $this->response->withStatus(422);
            $this->set([
                'success' => false,
                'message' => '保存に失敗しました',
                'errors' => $p->getErrors(),
            ]);
            $this->viewBuilder()->setOption('serialize', ['success','message','errors']);

            return;
        }

        // ---- PDF（任意）----
        try {
            $this->_handlePdfUploads($p);
        } catch (\Throwable $e) {
            $this->set(['pdf_warning' => $e->getMessage()]);
        }

        $this->set(['success' => true, 'id' => (int)$p->id]);
        $this->viewBuilder()->setOption('serialize', ['success','id']);
    }

    /**
     * GET /api/portfolios/view/{id}.json
     * ポートフォリオ詳細を返す（公開チェックあり）。`Users`, `Categories` を含め、いいね数等を整形。
     * 非公開は本人以外 403、例外時 500。
     *
     * @param int|string|null $id 対象ID
     * @return \Cake\Http\Response|null|void JSON をシリアライズして返却
     */
    public function view($id = null)
    {
        $this->request->allowMethod(['get']);

        try {
            // ★ 列を絞らない contain（icon_url が無くても落ちない）
            $p = $this->Portfolios->get($id, [
                'contain' => ['Users', 'Categories'],
            ]);

            // 公開チェック（必要なら）
            if ($p->is_public === false) {
                $identity = $this->request->getAttribute('identity');
                $authId = $identity ? (int)$identity->get('id') : null;
                if ($authId === null || (int)$p->user_id !== $authId) {
                    $this->response = $this->response->withStatus(403);
                    $this->set(['success' => false, 'message' => 'Forbidden']);
                    $this->viewBuilder()->setOption('serialize', ['success','message']);

                    return;
                }
            }

            // いいね数（テーブルが無くても動くように）
            $likeCount = 0;
            try {
                $this->Likes = $this->fetchTable('Likes');
                $likeCount = $this->Likes->find()->where(['portfolio_id' => $p->id])->count();
            } catch (\Throwable $e) {
                Log::warning('Likes lookup skipped: ' . $e->getMessage());
            }

            // サムネパスを /uploads/ に正規化（昔の /img/uploads を救済）
            $thumb = (string)($p->thumbnail ?? '');
            if (strpos($thumb, '/img/uploads/') === 0) {
                $thumb = preg_replace('#^/img/uploads/#', '/uploads/', $thumb);
            }

            // 補足PDF: 配列/JSON両対応で最終的に配列に
            $supp = $p->supplement_pdf_paths ?? [];
            if (is_string($supp)) {
                try {
                    $supp = json_decode($supp, true) ?: [];
                } catch (\Throwable $e) {
                    $supp = [];
                }
            }
            if (!is_array($supp)) {
                $supp = [];
            }
            $supp = array_values(array_map('strval', $supp));

            // ユーザー情報（アイコンは存在していそうな複数候補を順に）
            $userArr = null;
            if ($p->user) {
                $icon =
                    ($p->user->icon_url ?? null) ??
                    ($p->user->icon ?? null) ??
                    ($p->user->avatar ?? null) ??
                    ($p->user->image_url ?? null) ?? '';
                $userArr = [
                    'id' => (int)$p->user->id,
                    'name' => (string)($p->user->name ?? ''),
                    'icon_url' => (string)$icon,
                ];
            }

            $portfolio = [
                'id' => (int)$p->id,
                'title' => (string)($p->title ?? ''),
                'description' => (string)($p->description ?? ''),
                'thumbnail' => $thumb,
                'like_count' => (int)$likeCount,

                // 追加詳細
                'purpose' => (string)($p->purpose ?? ''),
                'basic_spec' => (string)($p->basic_spec ?? ''),
                'design_url' => (string)($p->design_url ?? ''),
                'design_description' => (string)($p->design_description ?? ''),
                'parts_list' => (string)($p->parts_list ?? ''),
                'processing_method' => (string)($p->processing_method ?? ''),
                'processing_notes' => (string)($p->processing_notes ?? ''),
                'analysis_method' => (string)($p->analysis_method ?? ''),
                'analysis_result' => (string)($p->analysis_result ?? ''),
                'development_period' => (string)($p->development_period ?? ''),
                'mechanical_notes' => (string)($p->mechanical_notes ?? ''),
                'reference_links' => (string)($p->reference_links ?? ''),
                'tool_used' => (string)($p->tool_used ?? ''),
                'material_used' => (string)($p->material_used ?? ''),
                'github_url' => (string)($p->github_url ?? ''),
                'experiment_summary' => (string)($p->experiment_summary ?? ''),

                // PDF
                'drawing_pdf_path' => (string)($p->drawing_pdf_path ?? ''),
                'supplement_pdf_paths' => $supp,

                // 関連
                'user' => $userArr,
                'category' => $p->category ? [
                    'id' => (int)$p->category->id,
                    'name' => (string)($p->category->name ?? ''),
                    'slug' => (string)($p->category->slug ?? ''),
                ] : null,
            ];

            $this->set(['success' => true, 'portfolio' => $portfolio]);
            $this->viewBuilder()->setOption('serialize', ['success','portfolio']);
        } catch (\Throwable $e) {
            Log::error('API portfolios/view error: ' . $e->getMessage());
            $this->response = $this->response->withStatus(500);
            $this->set(['success' => false, 'message' => $e->getMessage()]);
            $this->viewBuilder()->setOption('serialize', ['success','message']);
        }
    }

    // ---------------- PDF 保存ユーティリティ ----------------

    /**
     * ポートフォリオに紐づく PDF ファイル（図面・補足）を保存し、エンティティにパスを反映する。
     *
     * @param \App\Model\Entity\Portfolio $p 対象ポートフォリオ
     * @return void
     */
    private function _handlePdfUploads(\App\Model\Entity\Portfolio $p): void
    {
        $req = $this->request;
        $draw = $req->getData('drawing_pdf');
        $supps = (array)$req->getData('supplement_pdfs');

        if (!$draw && empty(array_filter($supps))) {
            return;
        }

        $baseDir = WWW_ROOT . 'files' . DS . 'portfolios' . DS . $p->id . DS;

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        if ($draw instanceof UploadedFileInterface && $draw->getError() === UPLOAD_ERR_OK) {
            $fname = $this->_moveOnePdf($draw, $baseDir, 'drawing', (int)$p->id);
            $p->drawing_pdf_path = 'files/portfolios/' . $p->id . '/' . $fname;
        }

        $paths = [];
        foreach ($supps as $f) {
            if ($f instanceof UploadedFileInterface && $f->getError() === UPLOAD_ERR_OK) {
                $fname = $this->_moveOnePdf($f, $baseDir, 'supplement', (int)$p->id);
                $paths[] = 'files/portfolios/' . $p->id . '/' . $fname;
            }
        }

        if ($paths) {
            $current = $p->supplement_pdf_paths;
            $currentArr = is_string($current) ? (array)json_decode($current, true) : (array)$current;
            $p->supplement_pdf_paths = json_encode(array_values(array_merge($currentArr, $paths)), JSON_UNESCAPED_SLASHES);
        }

        $this->Portfolios->saveOrFail($p);
    }

    /**
     * 単一のPDFを検証して保存し、保存ファイル名を返す。
     * - 拡張子・MIME が PDF か検証、20MB超は拒否。
     * - 保存先は `$baseDir` に `p-{pid}-{kind}-{rand}.pdf` で配置。
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file アップロードファイル
     * @param string $baseDir 保存ディレクトリ（末尾に DS を想定）
     * @param string $kind 種別（例: 'drawing' | 'supplement'）
     * @param int $pid ポートフォリオID
     * @return string 保存されたファイル名
     * @throws \RuntimeException 検証失敗やサイズ超過時
     */
    private function _moveOnePdf(UploadedFileInterface $file, string $baseDir, string $kind, int $pid): string
    {
        $ext = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            throw new \RuntimeException('PDFのみアップロードできます。');
        }

        $stream = $file->getStream();
        $contents = $stream->getContents();
        $stream->rewind();

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($contents);
        if (!in_array($mime, ['application/pdf','application/x-pdf'], true)) {
            throw new \RuntimeException('PDF以外のファイルです。');
        }
        if ($file->getSize() > 20 * 1024 * 1024) {
            throw new \RuntimeException('ファイルサイズは20MBまでです。');
        }

        $safeName = sprintf('p-%d-%s-%s.pdf', $pid, $kind, bin2hex(random_bytes(8)));
        $file->moveTo($baseDir . $safeName);

        return $safeName;
    }
}
