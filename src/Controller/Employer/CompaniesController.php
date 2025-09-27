<?php
declare(strict_types=1);

namespace App\Controller\Employer;

use App\Controller\AppController;
use Cake\Filesystem\Filesystem;
use Cake\Utility\Text;
use Psr\Http\Message\UploadedFileInterface;

class CompaniesController extends AppController
{
    /**
     * イニシャライズ
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        // Employer側は Companies で認証される前提（Applicationの設定どおり）
        $this->Authentication->addUnauthenticatedActions([]); // 全アクション認証必須
        // 既存ビューを流用
        $this->viewBuilder()->setTemplatePath('Companies');
        $this->Portfolios = $this->fetchTable('Portfolios');
    }

    /**
     * 自社編集（IDパラメータ不要）
     *
     * /employer/companies/edit
     *
     * @return \Cake\Http\Response|null 編集後はリダイレクトを返す。表示時は null。
     */
    public function edit()
    {
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            return $this->redirect('/employer/login');
        }

        // Employer認証のidentityは Companies エンティティ
        $companyId = (int)$identity->id;
        $company = $this->fetchTable('Companies')->get($companyId);

        if ($this->request->is(['patch','post','put'])) {
            $data = $this->request->getData();

            $uploaded = $this->request->getData('logo_file');

            if ($uploaded instanceof UploadedFileInterface && $uploaded->getError() === UPLOAD_ERR_OK) {
                // 軽いバリデーション
                $allowed = ['image/png','image/jpeg','image/webp','image/gif','image/svg+xml'];
                $mime = $uploaded->getClientMediaType() ?: '';
                $size = (int)$uploaded->getSize(); // bytes
                if (!in_array($mime, $allowed, true)) {
                    $this->Flash->error(__('Logo must be an image (png, jpg, webp, gif, svg).'));

                    return $this->redirect($this->request->getRequestTarget());
                }
                if ($size > 2 * 1024 * 1024) { // 2MB
                    $this->Flash->error(__('Logo is too large (max 2MB).'));

                    return $this->redirect($this->request->getRequestTarget());
                }

                // 拡張子決定（MIMEベース）
                $extMap = [
                    'image/png' => 'png',
                    'image/jpeg' => 'jpg',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif',
                    'image/svg+xml' => 'svg',
                ];
                $ext = $extMap[$mime] ?? 'bin';

                // 保存先（会社IDごとに分けても良い。ここでは単一ディレクトリ）
                $dir = WWW_ROOT . 'img' . DS . 'companies';
                $fs = new Filesystem();
                $fs->mkdir($dir, 0755, true); // 無ければ作成

                // ファイル名は衝突防止のために companyId + タイムスタンプ
                $filename = sprintf('company_%d_%d.%s', $companyId, time(), $ext);
                $dest = $dir . DS . $filename;

                // move
                $uploaded->moveTo($dest);

                // Web からの参照パス
                $webPath = '/img/companies/' . $filename;

                // 旧ファイルを掃除したい場合は、ここで $company->logo_path を見て unlink するなど（任意）

                // 保存用に data を上書き
                $data['logo_path'] = $webPath;
            }

            // slug 自動補完（空なら name から）
            if (empty($data['slug']) && !empty($data['name'])) {
                $data['slug'] = Text::slug((string)$data['name']);
            }

            // auth_email / auth_password を更新可能にしたい場合はそのままpatch
            // （auth_passwordはEntityのsetterで自動ハッシュされる想定）
            $company = $this->fetchTable('Companies')->patchEntity($company, $data);

            if ($this->fetchTable('Companies')->save($company)) {
                $this->Flash->success(__('Company updated.'));

                return $this->redirect([
                    'action' => 'view',
                    $company->id,
                ]);
            }
            $this->Flash->error(__('Update failed. Please try again.'));
        }

        $this->set(compact('company'));
        // 既存のテンプレをそのまま使う
        $this->render('edit'); // templates/Companies/edit.php
    }

    /**
     * 会社詳細を表示する
     *
     * @param int|null $id 会社ID。null の場合は例外発生。
     * @return void
     */
    public function view($id = null)
    {
        $company = $this->Companies->get($id, [
            'contain' => [],
        ]);

        $portfolios = $this->Portfolios->find()
            ->where(['company_id' => $company->id])
            ->order(['created' => 'DESC'])
            ->toArray();

        $this->set(compact('company', 'portfolios'));
    }
}
