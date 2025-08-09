<?php
// src/Controller/SettingsController.php
declare(strict_types=1);

namespace App\Controller;

use Cake\I18n\FrozenTime;
use Cake\Utility\Text;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use App\Mailer\UserMailer;

class SettingsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        // メール確認リンクは未ログインでも叩けるように
        if (method_exists($this->Authentication, 'addUnauthenticatedActions')) {
            $this->Authentication->addUnauthenticatedActions(['confirmEmail']);
        } else {
            $this->Authentication->allowUnauthenticated(['confirmEmail']);
        }

        $this->loadModel('Users');
        $this->loadComponent('Flash');
        // CSRF はミドルウェア想定。使っていなければ FormProtection を有効に
        // $this->loadComponent('FormProtection');
    }

    /**
     * 設定トップ：現在のメール、パスワード更新日時の表示のみ
     */
    public function index()
    {
        $identity = $this->request->getAttribute('identity');
        $userId = $identity->getIdentifier();
    
        $schema = $this->Users->getSchema();
    
        // 必須の2つだけは確実に
        $fields = ['id', 'email'];
    
        // あれば追加で取る（無ければ無視）
        if ($schema->hasColumn('modified')) {
            $fields[] = 'modified';
        }
        if ($schema->hasColumn('password_changed_at')) {
            $fields[] = 'password_changed_at';
        }
    
        $user = $this->Users->find()
            ->select($fields)
            ->where(['id' => $userId])
            ->firstOrFail();
    
        $this->set(compact('user'));
    }    

    /**
     * メール編集フォーム（GET）
     */
    public function editEmail()
    {
        $identity = $this->request->getAttribute('identity');
        $user = $this->Users->get($identity->getIdentifier(), [
            'fields' => ['id', 'email', 'new_email']
        ]);
        $this->set(compact('user'));
        $this->render('edit_email'); // templates/Settings/edit_email.php
    }

    /**
     * パスワード編集フォーム（GET）
     */
    public function editPassword()
    {
        $this->render('edit_password'); // templates/Settings/edit_password.php
    }

    /**
     * メール更新（POST）：確認メールを送信 → 確認リンクで確定
     */
    public function updateEmail()
    {
        $this->request->allowMethod(['post']);

        // --- 簡易レート制限（60秒間隔） ---
        $session = $this->request->getSession();
        $lastSent = $session->read('Settings.lastEmailRequestAt');
        if ($lastSent && (time() - (int)$lastSent) < 60) {
            $this->Flash->error('リクエストが多すぎます。しばらくしてからもう一度お試しください。');
            return $this->redirect(['action' => 'editEmail']);
        }

        $identity = $this->request->getAttribute('identity');
        $user = $this->Users->get($identity->getIdentifier());

        $currentPassword = (string)$this->request->getData('current_password');
        $newEmail = strtolower(trim((string)$this->request->getData('new_email')));

        // 現在パスワード確認
        $hasher = new DefaultPasswordHasher();
        if (!$hasher->check($currentPassword, (string)$user->password)) {
            $this->Flash->error('現在のパスワードが違います。');
            return $this->redirect(['action' => 'editEmail']);
        }

        // バリデーション（unique/format は validationEmailChange）
        $user = $this->Users->patchEntity($user, ['new_email' => $newEmail], [
            'validate' => 'emailChange'
        ]);
        if ($user->getErrors()) {
            $this->Flash->error('メールアドレスが不正か、既に使われています。');
            return $this->redirect(['action' => 'editEmail']);
        }

        // トークン発行（1時間有効）
        $token = Text::uuid() . bin2hex(random_bytes(16));
        $user->email_change_token = $token;
        $user->email_change_expires = new FrozenTime('+1 hour');

        if ($this->Users->save($user)) {
            // メール送信
            $mailer = new UserMailer();
            $mailer->emailChangeConfirm($user, $token);

            // 旧メールにも通知（任意）
            if (!empty($user->email)) {
                $mailer->emailChangeNoticeOld($user);
            }

            $session->write('Settings.lastEmailRequestAt', time());
            $this->Flash->success('確認メールを送信しました。メール内リンクで変更を完了してください。');
        } else {
            $this->Flash->error('メール変更の処理に失敗しました。');
        }

        return $this->redirect(['action' => 'editEmail']);
    }

    /**
     * メール確認リンク（未ログイン可）
     */
    public function confirmEmail(?string $token = null)
    {
        if (!$token) {
            $this->Flash->error('トークンがありません。');
            return $this->redirect(['action' => 'index']);
        }

        $user = $this->Users->find()
            ->where([
                'email_change_token' => $token,
                'email_change_expires >' => FrozenTime::now()
            ])->first();

        if (!$user || empty($user->new_email)) {
            $this->Flash->error('リンクが無効または期限切れです。');
            return $this->redirect(['action' => 'index']);
        }

        // 本メールへ反映
        $user->email = $user->new_email;
        $user->new_email = null;
        $user->email_change_token = null;
        $user->email_change_expires = null;

        if ($this->Users->save($user)) {
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
        $user = $this->Users->get($identity->getIdentifier());

        $currentPassword = (string)$this->request->getData('current_password');
        $newPassword = (string)$this->request->getData('new_password');
        $newPassword2 = (string)$this->request->getData('new_password_confirm');

        $hasher = new DefaultPasswordHasher();
        if (!$hasher->check($currentPassword, (string)$user->password)) {
            $this->Flash->error('現在のパスワードが違います。');
            return $this->redirect(['action' => 'editPassword']);
        }

        if ($newPassword !== $newPassword2) {
            $this->Flash->error('新しいパスワードが一致しません。');
            return $this->redirect(['action' => 'editPassword']);
        }

        $user = $this->Users->patchEntity($user, ['password' => $newPassword], [
            'validate' => 'passwordChange'
        ]);
        if ($user->getErrors()) {
            $this->Flash->error('パスワードがポリシーを満たしていません。');
            return $this->redirect(['action' => 'editPassword']);
        }

        // password_changed_at 列がある場合のみ更新
        if ($this->Users->getSchema()->hasColumn('password_changed_at')) {
            $user->set('password_changed_at', FrozenTime::now());
        }

        if ($this->Users->save($user)) {
            // セッション再生成（推奨）
            $this->request->getSession()->renew();
            $this->Flash->success('パスワードを変更しました。');
        } else {
            $this->Flash->error('変更に失敗しました。');
        }

        return $this->redirect(['action' => 'editPassword']);
    }
}
