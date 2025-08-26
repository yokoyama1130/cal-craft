<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Utility\Text;
use Cake\Event\EventInterface;

class CompaniesController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        // 必要に応じてフォーム/FlashなどのComponentロード
        // $this->loadComponent('Flash');
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        // indexアクションだけログイン不要にする
        $this->Authentication->addUnauthenticatedActions(['add']);
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
     * 会社作成：
     * - ログインユーザーの company が既にあれば edit にリダイレクト
     * - なければ owner_user_id を自動セットして作成
     */
    public function add()
    {
        if ($this->request->is('get')) {
            // フォームだけ表示（未ログインOK）
            $company = $this->Companies->newEmptyEntity();
            $this->set(compact('company'));
            return;
        }

        // ここからPOST系はログイン必須
        $identity = $this->getIdentity();
        if (!$identity) {
            $this->Flash->error(__('Please sign in.'));
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }
        $userId = (int)$identity;

        // 既存チェック
        $existing = $this->Companies->find()
            ->select(['id'])
            ->where(['owner_user_id' => $userId])
            ->first();
        if ($existing) {
            $this->Flash->info(__('You already have a company profile.'));
            return $this->redirect(['action' => 'edit', $existing->id]);
        }

        $company = $this->Companies->newEmptyEntity();
        $data = $this->request->getData();

        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = \Cake\Utility\Text::slug((string)$data['name']);
        }
        $data['owner_user_id'] = $userId;

        $company = $this->Companies->patchEntity($company, $data);
        if ($this->Companies->save($company)) {
            $this->Flash->success(__('Company has been created.'));
            return $this->redirect(['action' => 'view', $company->id]);
        }
        $this->Flash->error(__('Unable to create company. Please try again.'));
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
        // Authenticationプラグインが載っていれば request attribute に入ります
        $identity = $this->request->getAttribute('identity');
        if ($identity && method_exists($identity, 'getIdentifier')) {
            $id = $identity->getIdentifier();
            return $id !== null ? (int)$id : null;
        }
    
        // 直接コンポーネントから取るパターン（保険）
        if (property_exists($this, 'Authentication') && $this->Authentication->getIdentity()) {
            $id = $this->Authentication->getIdentity()->getIdentifier();
            return $id !== null ? (int)$id : null;
        }
    
        return null;
    }
}
