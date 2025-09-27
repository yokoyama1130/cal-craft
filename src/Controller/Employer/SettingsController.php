<?php
declare(strict_types=1);

namespace App\Controller\Employer;

use App\Controller\AppController;
use App\Mailer\UserMailer as CompanyMailer;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\I18n\FrozenTime;
use Cake\Utility\Text;

class SettingsController extends AppController
{
    /**
     * コントローラ初期化処理。
     *
     * - Companies テーブルを利用できるように設定
     * - Flash コンポーネントをロード
     * - メール認証リンク（confirmEmail）は未ログインでもアクセス可能に設定
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        // 会社側は Companies テーブルを使う
        $this->Companies = $this->fetchTable('Companies');
        $this->loadComponent('Flash');

        // メール確認リンクは未ログインでも叩けるように
        if (method_exists($this->Authentication, 'addUnauthenticatedActions')) {
            $this->Authentication->addUnauthenticatedActions(['confirmEmail']);
        } else {
            $this->Authentication->allowUnauthenticated(['confirmEmail']);
        }
    }

    /**
     * 設定トップ：現在メール/パスワード更新時刻を表示（項目が存在すれば）
     */
    public function index()
    {
        $identity = $this->request->getAttribute('identity');
        $companyId = (int)$identity->getIdentifier();

        $schema = $this->Companies->getSchema();

        $fields = ['id', 'auth_email'];
        if ($schema->hasColumn('modified')) {
            $fields[] = 'modified';
        }
        if ($schema->hasColumn('password_changed_at')) {
            $fields[] = 'password_changed_at';
        }

        $company = $this->Companies->find()
            ->select($fields)
            ->where(['id' => $companyId])
            ->firstOrFail();

        $this->set(compact('company'));
        // ビュー: templates/Employer/Settings/index.php
    }

    /**
     * メール編集フォーム（GET）
     */
    public function editEmail()
    {
        $identity = $this->request->getAttribute('identity');
        $company = $this->Companies->get((int)$identity->getIdentifier(), [
            'fields' => ['id', 'auth_email', 'new_email'],
        ]);
        $this->set(compact('company'));
        // ビュー: templates/Employer/Settings/edit_email.php （Users版を転用）
        $this->render('edit_email');
    }

    /**
     * パスワード編集フォーム（GET）
     */
    public function editPassword()
    {
        // ビュー: templates/Employer/Settings/edit_password.php （Users版を転用）
        $this->render('edit_password');
    }

    /**
     * メール更新（POST）: 確認メール送信→リンクで確定
     */
    public function updateEmail()
    {
        $this->request->allowMethod(['post']);

        // --- 簡易レート制限（60秒） ---
        $session = $this->request->getSession();
        $lastSent = $session->read('Employer.Settings.lastEmailRequestAt');
        if ($lastSent && (time() - (int)$lastSent) < 60) {
            $this->Flash->error('リクエストが多すぎます。しばらくしてからもう一度お試しください。');

            return $this->redirect(['action' => 'editEmail']);
        }

        $identity = $this->request->getAttribute('identity');
        $company = $this->Companies->get((int)$identity->getIdentifier());

        $currentPassword = (string)$this->request->getData('current_password');
        $newEmail = strtolower(trim((string)$this->request->getData('new_email')));

        // 現在パスワード確認
        $hasher = new DefaultPasswordHasher();
        if (!$hasher->check($currentPassword, (string)$company->password)) {
            $this->Flash->error('現在のパスワードが違います。');

            return $this->redirect(['action' => 'editEmail']);
        }

        // バリデーション名は CompaniesTable 側に emailChange ルールを用意（Users と同じ要領）
        $company = $this->Companies->patchEntity($company, ['new_email' => $newEmail], [
            'validate' => 'emailChange',
        ]);
        if ($company->getErrors()) {
            $this->Flash->error('メールアドレスが不正か、既に使われています。');

            return $this->redirect(['action' => 'editEmail']);
        }

        // トークン発行（1時間有効）
        $token = Text::uuid() . bin2hex(random_bytes(16));
        $company->email_change_token = $token;
        $company->email_change_expires = new FrozenTime('+1 hour');

        if ($this->Companies->save($company)) {
            // 会社向けメール送信（Mailer 名は環境に合わせて。ここでは UserMailer を流用）
            $mailer = new CompanyMailer();
            $mailer->emailChangeConfirm($company, $token);

            if (!empty($company->email)) {
                $mailer->emailChangeNoticeOld($company);
            }

            $session->write('Employer.Settings.lastEmailRequestAt', time());
            $this->Flash->success('確認メールを送信しました。メール内リンクで変更を完了してください。');
        } else {
            $this->Flash->error('メール変更の処理に失敗しました。');
        }

        return $this->redirect(['action' => 'editEmail']);
    }

    /**
     * メール確認リンク（未ログイン可）
     * /employer/settings/email/confirm/{token}
     */
    public function confirmEmail(?string $token = null)
    {
        if (!$token) {
            $this->Flash->error('トークンがありません。');

            return $this->redirect(['action' => 'index']);
        }

        $company = $this->Companies->find()
            ->where([
                'email_change_token' => $token,
                'email_change_expires >' => FrozenTime::now(),
            ])->first();

        if (!$company || empty($company->new_email)) {
            $this->Flash->error('リンクが無効または期限切れです。');

            return $this->redirect(['action' => 'index']);
        }

        // 反映
        $company->email = $company->new_email;
        $company->new_email = null;
        $company->email_change_token = null;
        $company->email_change_expires = null;

        if ($this->Companies->save($company)) {
            $this->Flash->success('メールアドレスを変更しました。');
        } else {
            $this->Flash->error('変更に失敗しました。');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * パスワード更新（POST）
     */
    public function updatePassword()
    {
        $this->request->allowMethod(['post']);

        $identity = $this->request->getAttribute('identity');
        $company = $this->Companies->get((int)$identity->getIdentifier());

        $currentPassword = (string)$this->request->getData('current_password');
        $newPassword = (string)$this->request->getData('new_password');
        $newPassword2 = (string)$this->request->getData('new_password_confirm');

        $hasher = new DefaultPasswordHasher();
        if (!$hasher->check($currentPassword, (string)$company->password)) {
            $this->Flash->error('現在のパスワードが違います。');

            return $this->redirect(['action' => 'editPassword']);
        }

        if ($newPassword !== $newPassword2) {
            $this->Flash->error('新しいパスワードが一致しません。');

            return $this->redirect(['action' => 'editPassword']);
        }

        // CompaniesTable 側に Users と同じ validation set（passwordChange）を用意しておく
        $company = $this->Companies->patchEntity($company, ['password' => $newPassword], [
            'validate' => 'passwordChange',
        ]);
        if ($company->getErrors()) {
            $this->Flash->error('パスワードがポリシーを満たしていません。');

            return $this->redirect(['action' => 'editPassword']);
        }

        // password_changed_at があれば更新
        if ($this->Companies->getSchema()->hasColumn('password_changed_at')) {
            $company->set('password_changed_at', FrozenTime::now());
        }

        if ($this->Companies->save($company)) {
            $this->request->getSession()->renew();
            $this->Flash->success('パスワードを変更しました。');
        } else {
            $this->Flash->error('変更に失敗しました。');
        }

        return $this->redirect(['action' => 'editPassword']);
    }

    public function deleteConfirm()
    {
        // ビュー: templates/Employer/Settings/delete_confirm.php
    }

    public function deleteAccount()
    {
        $this->request->allowMethod(['post']);

        $identity = $this->request->getAttribute('identity');
        $company = $this->Companies->get((int)$identity->getIdentifier());

        // 再認証：現在パスワード
        $hasher = new \Authentication\PasswordHasher\DefaultPasswordHasher();
        $currentPassword = (string)$this->request->getData('current_password');
        if (!$hasher->check($currentPassword, (string)$company->password)) {
            $this->Flash->error('現在のパスワードが違います。');

            return $this->redirect(['action' => 'deleteConfirm']);
        }

        // 確認キーワード
        $confirm = (string)$this->request->getData('confirm_keyword');
        if (strtoupper(trim($confirm)) !== 'DELETE') {
            $this->Flash->error('確認キーワードが一致しません。DELETE と入力してください。');

            return $this->redirect(['action' => 'deleteConfirm']);
        }

        // ソフトデリート＋匿名化（Companies のカラムに合わせて調整）
        if ($this->Companies->getSchema()->hasColumn('deleted_at')) {
            $company->deleted_at = FrozenTime::now();
        }
        $company->email = sprintf('deleted-company+%d@invalid.example', $company->id);
        if ($this->Companies->getSchema()->hasColumn('new_email')) $company->set('new_email', null);
        if ($this->Companies->getSchema()->hasColumn('email_change_token')) $company->set('email_change_token', null);
        if ($this->Companies->getSchema()->hasColumn('email_change_expires')) $company->set('email_change_expires', null);
        $company->password = bin2hex(random_bytes(16)); // 再ログイン不能に

        if ($this->Companies->save($company)) {
            // ログアウト
            $this->Authentication->logout();
            $this->request->getSession()->destroy();
            $this->Flash->success('会社アカウントを削除（無効化）しました。');

            return $this->redirect(['prefix' => false, 'controller' => 'Pages', 'action' => 'display', 'home']);
        }

        $this->Flash->error('削除に失敗しました。時間をおいて再度お試しください。');

        return $this->redirect(['action' => 'deleteConfirm']);
    }
}
