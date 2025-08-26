<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Utility\Text;
use Cake\Event\EventInterface;
use Cake\Datasource\ConnectionManager;

class CompaniesController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        // 認証コンポーネントが未ロードなら AppController で $this->loadComponent('Authentication.Authentication') を
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        // 未ログインでも add を許可
        // ★メソッド名は allowUnauthenticated が正
        $this->Authentication->allowUnauthenticated(['add']);
    }

    /**
     * 会社作成：
     * - ログインユーザーの company が既にあれば edit にリダイレクト
     * - なければ owner_user_id を自動セットして作成
     */
    public function add()
    {
        $company = $this->Companies->newEmptyEntity();

        if ($this->request->is('get')) {
            $this->set(compact('company'));
            return;
        }

        $data = $this->request->getData();

        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = Text::slug((string)$data['name']);
        }

        // ★ owner_* → auth_* にマップ（フォーム名はそのままでOK）
        $ownerEmail    = trim((string)($data['owner_email'] ?? $data['billing_email'] ?? ''));
        $ownerPassword = (string)($data['owner_password'] ?? '');

        if ($ownerEmail === '') {
            throw new \RuntimeException('Owner email is required.');
        }
        if ($ownerPassword === '') {
            $ownerPassword = bin2hex(random_bytes(8)); // 最低8文字以上に
        }
        $data['auth_email']    = $ownerEmail;
        $data['auth_password'] = $ownerPassword; // ★エンティティでハッシュされる

        // もし “一般ユーザーIDをオーナーとして保持したい”なら、別カラムに保持（任意）
        // $data['owner_user_id'] = $this->getIdentity() ?? null;

        // 保存
        $company = $this->Companies->patchEntity($company, $data /* , ['validate' => 'employerLogin'] */);
        if (!$this->Companies->save($company)) {
            $errors = $company->getErrors();
            $this->Flash->error('Unable to create company: ' . json_encode($errors));
            $this->set(compact('company'));
            return;
        }

        // 作成完了 → 企業ログイン画面へ誘導（email を自動入力したいならクエリで渡す）
        $this->Flash->success(__('Company has been created. Please sign in to Employer Console.'));
        return $this->redirect('/employer/login?auth_email=' . urlencode($ownerEmail));
    }


    /**
     * 会社一覧（必要なければ消してOK）
     * 多分いらないかも
     */
    public function index()
    {
        $companies = $this->paginate($this->Companies->find());
        $query = $this->Companies->find()
        ->contain(['Users']); // owner_user_id -> Users との belongsTo
        $this->paginate = ['limit' => 20, 'order' => ['Companies.modified' => 'DESC']];
        $companies = $this->paginate($query);
        $this->set(compact('companies'));
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

    /**
     * 会社編集：
     * - オーナーのみ編集可
     * - slug 未入力なら name から自動補完
     */
    public function edit($id = null)
    {
        $identity = $this->getIdentity();
        if (!$identity) {
            $this->Flash->error(__('Please sign in.'));
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }
        $userId = (int)$identity;

        $company = $this->Companies->get($id);

        if ((int)$company->owner_user_id !== $userId) {
            $this->Flash->error(__('Not authorized.'));
            return $this->redirect(['action' => 'view', $company->id]);
        }

        if ($this->request->is(['patch','post','put'])) {
            $data = $this->request->getData();

            if (empty($data['slug']) && !empty($data['name'])) {
                $data['slug'] = Text::slug((string)$data['name']);
            }

            // セキュリティ：owner_user_id を外部から書き換えられないように除外
            unset($data['owner_user_id']);

            $company = $this->Companies->patchEntity($company, $data);
            if ($this->Companies->save($company)) {
                $this->Flash->success(__('Company updated.'));
                return $this->redirect(['action' => 'view', $company->id]);
            }
            $this->Flash->error(__('Update failed. Please try again.'));
        }

        $this->set(compact('company'));
    }

    /**
     * 会社削除（必要なら）
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $identity = $this->getIdentity();
        if (!$identity) {
            $this->Flash->error(__('Please sign in.'));
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }
        $userId = (int)$identity;

        $company = $this->Companies->get($id);

        if ((int)$company->owner_user_id !== $userId) {
            $this->Flash->error(__('Not authorized.'));
            return $this->redirect(['action' => 'view', $company->id]);
        }

        if ($this->Companies->delete($company)) {
            $this->Flash->success(__('Company deleted.'));
            return $this->redirect(['action' => 'index']);
        }
        $this->Flash->error(__('Delete failed.'));
        return $this->redirect(['action' => 'view', $company->id]);
    }

    /**
     * 自分の会社にショートカット
     * - 会社があれば view へ
     * - なければ add へ
     */
    public function my()
    {
        $identity = $this->getIdentity();
        if (!$identity) {
            $this->Flash->error(__('Please sign in.'));
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }
        $userId = (int)$identity;

        $company = $this->Companies->find()
            ->select(['id'])
            ->where(['owner_user_id' => $userId])
            ->first();

        if ($company) {
            return $this->redirect(['action' => 'view', $company->id]);
        }
        return $this->redirect(['action' => 'add']);
    }

    /**
     * Authenticationプラグイン/Request属性の両対応で ID を取り出す小ヘルパ
     */
    private function getIdentity(): ?int
    {
        $identity = $this->request->getAttribute('identity');
        if ($identity && method_exists($identity, 'getIdentifier')) {
            $id = $identity->getIdentifier();
            return $id !== null ? (int)$id : null;
        }
        if (property_exists($this, 'Authentication') && $this->Authentication->getIdentity()) {
            $id = $this->Authentication->getIdentity()->getIdentifier();
            return $id !== null ? (int)$id : null;
        }
        return null;
    }
}
