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
    public function initialize(): void
    {
        parent::initialize();
        $this->Portfolios = $this->fetchTable('Portfolios');
        $this->viewBuilder()->setClassName('Json');
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        if ($this->components()->has('Authentication')) {
            $this->Authentication->allowUnauthenticated([]);
        }
    }

    /**
     * POST /api/portfolios/add.json
     */
    public function add()
    {
        $this->request->allowMethod(['post']);

        $identity = $this->request->getAttribute('identity');
        if (!$identity) {
            $this->response = $this->response->withStatus(401);
            // ※ _serialize は非推奨なので setOption('serialize') を使用
            $this->set(['success' => false, 'message' => 'Unauthorized']);
            $this->viewBuilder()->setOption('serialize', ['success','message']);

            return;
        }

        // デバッグ（必要なら残す）
        Log::debug('[add] CT=' . $this->request->getHeaderLine('Content-Type'));

        $p = $this->Portfolios->newEmptyEntity();
        $p->user_id = (int)$identity->get('id');
        $p->company_id = null;

        $data = (array)$this->request->getData();

        // ===== サムネ処理 =====
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
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $thumbnailFile->moveTo($dir . $filename);

            $data['thumbnail'] = '/uploads/' . $filename;
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

        // ===== ここがポイント：TEXT NOT NULL 欄は未指定/NULLなら空文字に埋める =====
        $textNotNull = [
            // 共通
            'link',
            // 機械系
            'purpose','basic_spec','design_url','design_description','parts_list',
            'processing_method','processing_notes','analysis_method','analysis_result',
            'development_period','mechanical_notes','reference_links','tool_used','material_used',
            // プログラミング／化学
            'github_url','experiment_summary',
        ];
        foreach ($textNotNull as $f) {
            if (!array_key_exists($f, $data) || $data[$f] === null) {
                $data[$f] = ''; // TEXT はデフォルト不可なので必ず '' を入れる
            }
        }
        // description はバリデーションで必須なので、最低1文字は入っている想定
        // 念のためNULLなら空文字に
        if (!isset($data['description'])) {
            $data['description'] = '';
        }

        // ===== 保存 =====
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

        // ===== PDF（任意） =====
        try {
            $this->_handlePdfUploads($p);
        } catch (\Throwable $e) {
            $this->set(['pdf_warning' => $e->getMessage()]);
        }

        $this->set(['success' => true, 'id' => (int)$p->id]);
        $this->viewBuilder()->setOption('serialize', ['success','id']);
    }

    private function _handlePdfUploads(\App\Model\Entity\Portfolio $p): void
    {
        $req = $this->request;
        $drawing = $req->getData('drawing_pdf');
        $supps = (array)$req->getData('supplement_pdfs');

        if (!$drawing && empty(array_filter($supps))) return;

        $baseDir = WWW_ROOT . 'files' . DS . 'portfolios' . DS . $p->id . DS;
        if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);

        if ($drawing instanceof UploadedFileInterface && $drawing->getError() === UPLOAD_ERR_OK) {
            $fname = $this->_moveOnePdf($drawing, $baseDir, 'drawing', (int)$p->id);
            $p->drawing_pdf_path = 'files/portfolios/' . $p->id . '/' . $fname;
        }

        $suppPaths = [];
        foreach ($supps as $f) {
            if ($f instanceof UploadedFileInterface && $f->getError() === UPLOAD_ERR_OK) {
                $fname = $this->_moveOnePdf($f, $baseDir, 'supplement', (int)$p->id);
                $suppPaths[] = 'files/portfolios/' . $p->id . '/' . $fname;
            }
        }

        if ($suppPaths) {
            $current = $p->supplement_pdf_paths;
            $currentArr = is_string($current) ? (array)json_decode($current, true) : (array)$current;
            $p->supplement_pdf_paths = json_encode(array_values(array_merge($currentArr, $suppPaths)), JSON_UNESCAPED_SLASHES);
        }

        $this->Portfolios->saveOrFail($p);
    }

    private function _moveOnePdf(UploadedFileInterface $file, string $baseDir, string $kind, int $pid): string
    {
        $ext = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        if ($ext !== 'pdf') throw new \RuntimeException('PDFのみアップロードできます。');

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
