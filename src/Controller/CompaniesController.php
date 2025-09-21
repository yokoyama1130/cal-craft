<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Utility\Text;
use Cake\Event\EventInterface;
use Cake\Datasource\ConnectionManager;
use Psr\Http\Message\UploadedFileInterface;
use Cake\Filesystem\Folder;
use Cake\Mailer\Mailer;
use Cake\Routing\Router;

class CompaniesController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('Portfolios');
        $this->loadModel('Companies');
        $this->Authentication->addUnauthenticatedActions([]); // すべて認証必須
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

        $uploaded = $this->request->getData('logo_file');
        if ($uploaded instanceof UploadedFileInterface && $uploaded->getError() === UPLOAD_ERR_OK) {
            // 簡易バリデーション
            $allowed = ['image/png','image/jpeg','image/webp','image/gif','image/svg+xml'];
            $mime = $uploaded->getClientMediaType() ?: '';
            $size = (int)$uploaded->getSize();
            if (!in_array($mime, $allowed, true)) {
                $this->Flash->error(__('Logo must be an image (png, jpg, webp, gif, svg).'));
                return $this->redirect($this->request->getRequestTarget());
            }
            if ($size > 2*1024*1024) { // 2MB
                $this->Flash->error(__('Logo is too large (max 2MB).'));
                return $this->redirect($this->request->getRequestTarget());
            }

            $extMap = [
                'image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp',
                'image/gif'=>'gif','image/svg+xml'=>'svg',
            ];
            $ext = $extMap[$mime] ?? 'bin';

            $dir = WWW_ROOT . 'img' . DS . 'companies';
            (new Folder($dir, true, 0755)); // 無ければ作る

            // まだIDは無いので、一旦タイムスタンプ＋乱数で
            $filename = sprintf('company_new_%d_%04d.%s', time(), random_int(0,9999), $ext);
            $dest = $dir . DS . $filename;

            $uploaded->moveTo($dest);

            // Webからのパス
            $data['logo_path'] = '/img/companies/' . $filename;
        }

        // ★ owner_* → auth_* にマップ（フォーム名はそのままでOK）
        $ownerEmail    = trim((string)($data['owner_email'] ?? $data['billing_email'] ?? ''));
        $ownerPassword = (string)($data['owner_password'] ?? '');

        // ★ 必須チェック（未入力はエラー）
        if ($ownerEmail === '') {
            $this->Flash->error('オーナー用メールは必須です。');
            $this->set(compact('company'));
            return;
        }
        if ($ownerPassword === '') {
            $this->Flash->error('オーナー用パスワードは必須です。');
            $this->set(compact('company'));
            return;
        }
        if (mb_strlen($ownerPassword) < 8) { // 任意
            $this->Flash->error('パスワードは8文字以上にしてください。');
            $this->set(compact('company'));
            return;
        }
        $data['auth_email']    = $ownerEmail;
        $data['auth_password'] = $ownerPassword; // ★エンティティでハッシュされる

        // もし “一般ユーザーIDをオーナーとして保持したい”なら、別カラムに保持（任意）
        // $data['owner_user_id'] = $this->getIdentity() ?? null;

        $data['email_verified'] = false;
        $data['email_token']    = Text::uuid();

        // 保存
        $company = $this->Companies->patchEntity($company, $data /* , ['validate' => 'employerLogin'] */);
        if (!$this->Companies->save($company)) {
            $errors = $company->getErrors();
            $this->Flash->error('Unable to create company: ' . json_encode($errors));
            $this->set(compact('company'));
            return;
        }

        $mailer = new Mailer('default');
        $mailer->setTo($ownerEmail)
            ->setSubject('【OrcaFolio】企業メール認証のお願い')
            ->deliver(
                "以下のURLをクリックしてメール認証を完了してください：\n\n" .
                Router::url(
                [
                    'prefix' => 'Employer',
                    'controller' => 'Auth',
                    'action' => 'verifyEmail',
                    $company->email_token
                ],
                true
                )
            );

        $this->Flash->success(__('確認メールを送信しました。メールをご確認ください。'));
        // 企業ログインページへ誘導
        $this->Authentication->logout();
        return $this->redirect('/employer/login?auth_email=' . urlencode($ownerEmail));


        // // 作成完了 → 企業ログイン画面へ誘導（email を自動入力したいならクエリで渡す）
        // $this->Flash->success(__('Company has been created. Please sign in to Employer Console.'));
        // // ★ いったんEmployerセッションを破棄してからログイン画面へ
        // $this->Authentication->logout();
        // return $this->redirect('/employer/login?auth_email=' . urlencode($ownerEmail));
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

        $portfolios = $this->Portfolios->find()
            ->where(['company_id' => $company->id])
            ->order(['created' => 'DESC'])
            ->toArray();

        $this->set(compact('company', 'portfolios'));
    }

    /**
     * 会社編集：
     * - オーナーのみ編集可
     * - slug 未入力なら name から自動補完
     */
    public function edit($id = null)
    {
        // Employer の identity は「Companies の認証ID = company.id」を想定
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            $this->Flash->error('Please sign in as Employer.');
            return $this->redirect(['prefix'=>'Employer','controller'=>'Auth','action'=>'login']);
        }
        $authedCompanyId = (int)$identity->get('id');

        $company = $this->Companies->get($id);

        if ((int)$company->id !== $authedCompanyId) {
            $this->Flash->error('Not authorized.');
            return $this->redirect(['prefix'=>'Employer','controller'=>'Dashboard','action'=>'index']);
        }

        if ($this->request->is(['patch','post','put'])) {
            $data = $this->request->getData();

            if (empty($data['slug']) && !empty($data['name'])) {
                $data['slug'] = Text::slug((string)$data['name']);
            }

            // 会社編集で外から owner_user_id を書き換えられないように
            unset($data['owner_user_id']);

            $company = $this->Companies->patchEntity($company, $data);
            if ($this->Companies->save($company)) {
                $this->Flash->success('Company updated.');
                return $this->redirect(['action' => 'edit', $company->id]); // or view
            }
            $this->Flash->error('Update failed. Please try again.');
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
