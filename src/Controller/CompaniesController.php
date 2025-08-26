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

        // POST以降
        $data = $this->request->getData();

        // 会社スラッグ自動生成
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = Text::slug((string)$data['name']);
        }

        // ★トランザクション開始
        $conn = ConnectionManager::get('default');
        $conn->begin();

        try {
            // 1) オーナーIDの決定
            $ownerUserId = $this->getIdentity(); // ログイン中ならIDが入る

            if (!$ownerUserId) {
                // 未ログイン → オーナーユーザーを自動作成
                // フォームから受ける想定のフィールド:
                // - owner_email（必須推奨）
                // - owner_password（任意。未入力ならランダム）
                $ownerEmail = $data['owner_email'] ?? null;
                $ownerPassword = $data['owner_password'] ?? null;

                if (empty($ownerEmail)) {
                    // 必須にするならここでエラーにする
                    throw new \RuntimeException('Owner email is required.');
                }
                if (empty($ownerPassword)) {
                    // ランダム発行（あとでパスワード変更導線を送る運用がおすすめ）
                    $ownerPassword = bin2hex(random_bytes(8)); // 16桁
                }

                /** @var \App\Model\Table\UsersTable $Users */
                $Users = $this->fetchTable('Users');
                $ownerUser = $Users->newEntity([
                    'email' => $ownerEmail,
                    'password' => $ownerPassword,
                    'role' => 'employer',      // 例：企業用ロール。存在しなければ追加
                    'status' => 'active',      // 例：状態フラグ。不要なら削除
                ], ['validate' => 'default']);

                if (!$Users->save($ownerUser)) {
                    throw new \RuntimeException('Failed to create owner user.');
                }
                $ownerUserId = (int)$ownerUser->id;

                // TODO: ここで「初回パスワード設定メール」や「認証メール」を送る処理を入れると◎
            }

            // 2) Company 保存
            $data['owner_user_id'] = $ownerUserId; // ★必ずサーバ側で上書き
            unset($data['id']); // 念のため

            $company = $this->Companies->patchEntity($company, $data);
            if (!$this->Companies->save($company)) {
                throw new \RuntimeException('Failed to create company.');
            }

            $conn->commit();

            $this->Flash->success(__('Company has been created.'));
            return $this->redirect(['action' => 'view', $company->id]);

        } catch (\Throwable $e) {
            $conn->rollback();
            $this->Flash->error(__('Unable to create company: ') . $e->getMessage());
        }

        $this->set(compact('company'));
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
