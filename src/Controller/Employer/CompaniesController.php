<?php
declare(strict_types=1);

namespace App\Controller\Employer;

use App\Controller\AppController;
use Cake\Utility\Text;

class CompaniesController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        // Employer側は Companies で認証される前提（Applicationの設定どおり）
        $this->Authentication->addUnauthenticatedActions([]); // 全アクション認証必須
        // 既存ビューを流用
        $this->viewBuilder()->setTemplatePath('Companies');
    }

    /**
     * 自社編集（IDパラメータ不要）
     * /employer/companies/edit
     */
    public function edit()
    {
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            return $this->redirect('/employer/login');
        }

        // Employer認証のidentityは Companies エンティティ
        $companyId = (int)$identity->id;
        $company   = $this->fetchTable('Companies')->get($companyId);

        if ($this->request->is(['patch','post','put'])) {
            $data = $this->request->getData();

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
     * 会社詳細
     */
    public function view($id = null)
    {
        $company = $this->Companies->get($id, [
            'contain' => [],
        ]);
        $this->set(compact('company'));
    }
}
