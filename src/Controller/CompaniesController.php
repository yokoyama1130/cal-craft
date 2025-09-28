<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Mailer\Mailer;
use Cake\Routing\Router;
use Cake\Utility\Text;
use Psr\Http\Message\UploadedFileInterface;

class CompaniesController extends AppController
{
    /**
     * コントローラ初期化処理
     *
     * 利用するテーブル（Portfolios, Companies）を読み込み、
     * 認証コンポーネントの未認証アクションを設定します。
     * ここではすべてのアクションを認証必須にしています。
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Portfolios = $this->fetchTable('Portfolios');
        $this->Companies = $this->fetchTable('Companies');
        $this->Authentication->addUnauthenticatedActions([]); // すべて認証必須
        // 認証コンポーネントが未ロードなら AppController で $this->loadComponent('Authentication.Authentication') を
    }

    /**
     * コントローラのアクション実行前処理
     *
     * 未ログインのユーザーにも一部アクション（ここでは add）を許可します。
     * Authentication プラグインの allowUnauthenticated を利用して制御します。
     *
     * @param \Cake\Event\EventInterface $event イベントオブジェクト
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        // 未ログインでも add を許可
        // ★メソッド名は allowUnauthenticated が正
        $this->Authentication->allowUnauthenticated(['add']);
    }

    /**
     * 会社アカウント作成アクション
     *
     * - 企業情報を新規作成し、ロゴ画像のアップロードや
     *   オーナー用メール・パスワードの必須チェックを行います。
     * - 入力データに基づいて `auth_email` と `auth_password` を設定し、
     *
     * @return void
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
            if ($size > 2 * 1024 * 1024) { // 2MB
                $this->Flash->error(__('Logo is too large (max 2MB).'));

                return $this->redirect($this->request->getRequestTarget());
            }

            $extMap = [
                'image/png' => 'png' , 'image/jpeg' => 'jpg' , 'image/webp' => 'webp',
                'image/gif' => 'gif' , 'image/svg+xml' => 'svg',
            ];
            $ext = $extMap[$mime] ?? 'bin';

            $dir = WWW_ROOT . 'img' . DS . 'companies';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            } // 無ければ作る

            // まだIDは無いので、一旦タイムスタンプ＋乱数で
            $filename = sprintf('company_new_%d_%04d.%s', time(), random_int(0, 9999), $ext);
            $dest = $dir . DS . $filename;

            $uploaded->moveTo($dest);

            // Webからのパス
            $data['logo_path'] = '/img/companies/' . $filename;
        }

        // ★ owner_* → auth_* にマップ（フォーム名はそのままでOK）
        $ownerEmail = trim((string)($data['owner_email'] ?? $data['billing_email'] ?? ''));
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
        $data['auth_email'] = $ownerEmail;
        $data['auth_password'] = $ownerPassword; // ★エンティティでハッシュされる

        // もし “一般ユーザーIDをオーナーとして保持したい”なら、別カラムに保持（任意）
        // $data['owner_user_id'] = $this->getIdentity() ?? null;

        $data['email_verified'] = false;
        $data['email_token'] = Text::uuid();

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
                        $company->email_token,
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
    // public function index()
    // {
    //     $companies = $this->paginate($this->Companies->find());
    //     $query = $this->Companies->find()
    //     ->contain(['Users']); // owner_user_id -> Users との belongsTo
    //     $this->paginate = ['limit' => 20, 'order' => ['Companies.modified' => 'DESC']];
    //     $companies = $this->paginate($query);
    //     $this->set(compact('companies'));
    // }

    /**
     * 会社詳細表示アクション
     *
     * 指定された ID の会社情報を取得し、その会社に紐づく
     * ポートフォリオ一覧を作成日時の降順で取得してビューに渡します。
     *
     * @param int|null $id 会社ID
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

    /**
     * 会社編集アクション
     *
     * - 認証済みの企業アカウント本人（Employer）のみ編集可能
     * - 指定されたIDの会社がログイン中の企業と一致しない場合はエラー
     * - slug が未入力の場合は name から自動生成して補完
     * - owner_user_id は外部から改変できないよう除外
     * - 更新に成功すれば会社詳細ページへリダイレクト
     * - 更新失敗や認可エラー時はフラッシュメッセージを表示してリダイレクト
     *
     * @param int|null $id 会社ID
     * @return \Cake\Http\Response|null 編集成功時はリダイレクトレスポンス、失敗時はビューを再表示
     */
    public function edit($id = null)
    {
        // Employer の identity は「Companies の認証ID = company.id」を想定
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            $this->Flash->error('Please sign in as Employer.');

            return $this->redirect(['prefix' => 'Employer' , 'controller' => 'Auth' , 'action' => 'login']);
        }
        $authedCompanyId = (int)$identity->get('id');

        $company = $this->Companies->get($id);

        if ((int)$company->id !== $authedCompanyId) {
            $this->Flash->error('Not authorized.');

            return $this->redirect(['prefix' => 'Employer' , 'controller' => 'Dashboard' , 'action' => 'index']);
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
                $this->Flash->success('会社情報を編集しました。');

                return $this->redirect(['action' => 'view', $company->id]); // or view
            }
            $this->Flash->error('Update failed. Please try again.');
        }

        $this->set(compact('company'));
    }

    /**
     * 会社削除アクション
     *
     * - 認証済みの一般ユーザー（owner_user_id が一致するユーザー）のみ削除可能
     * - 未ログインの場合はログイン画面へリダイレクト
     * - 所有者以外がアクセスした場合はエラーメッセージを表示して会社詳細へリダイレクト
     * - 削除成功時は「index」へリダイレクトし、失敗時は元の会社詳細ページに戻ります
     *
     * @param int|null $id 会社ID
     * @return \Cake\Http\Response|null 削除後のリダイレクトレスポンス
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
     * 自分の会社ショートカットアクション
     *
     * - ログインユーザーが所有する会社を検索し、
     *   存在すればその会社の詳細ページ (view) へリダイレクト
     * - 存在しなければ会社作成ページ (add) へリダイレクト
     * - 未ログインの場合はログインページへリダイレクト
     *
     * @return \Cake\Http\Response|null リダイレクトレスポンス
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
     * 現在ログイン中のユーザーIDを取得するヘルパー
     *
     * Authentication プラグインの Identity または
     * Request 属性 `identity` の両方に対応します。
     *
     * - Identity オブジェクトが存在すれば getIdentifier() を呼び出し、
     *   null でなければ int にキャストして返します。
     * - それ以外の場合は Authentication コンポーネントから Identity を取得します。
     * - どちらも存在しない場合は null を返します。
     *
     * @return int|null ログイン中のユーザーID、未ログイン時は null
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
