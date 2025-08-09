<?php

// src/Controller/SettingsController.php
declare(strict_types=1);

namespace App\Controller;

use Cake\I18n\FrozenTime;
use Cake\Utility\Text;
use Authentication\PasswordHasher\DefaultPasswordHasher;

class SettingsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        // confirmEmail は未ログインでも到達できるように
        if (method_exists($this->Authentication, 'addUnauthenticatedActions')) {
            $this->Authentication->addUnauthenticatedActions(['confirmEmail']);
        } else {
            $this->Authentication->allowUnauthenticated(['confirmEmail']);
        }

        $this->loadModel('Users');
        $this->loadComponent('Flash');
        // CSRF はミドルウェアで入っている想定。FormProtectionを使うなら↓
        // $this->loadComponent('FormProtection');
    }

    public function index()
    {
        $identity = $this->request->getAttribute('identity');
        $user = $identity ? $identity->getOriginalData() : null;
        $this->set(compact('user'));
    }

    public function updateEmail()
    {
        $this->request->allowMethod(['post']);

        $identity = $this->request->getAttribute('identity');
        $user = $this->Users->get($identity->getIdentifier());

        $currentPassword = (string)$this->request->getData('current_password');
        $newEmail = strtolower(trim((string)$this->request->getData('new_email')));

        // 現在パスワード確認
        $hasher = new DefaultPasswordHasher();
        if (!$hasher->check($currentPassword, (string)$user->password)) {
            $this->Flash->error('現在のパスワードが違います。');
            return $this->redirect(['action' => 'index']);
        }

        // バリデーション（unique/format は validationEmailChange へ）
        $user = $this->Users->patchEntity($user, ['new_email' => $newEmail], [
            'validate' => 'emailChange'
        ]);
        if ($user->getErrors()) {
            $this->Flash->error('メールアドレスが不正か、既に使われています。');
            return $this->redirect(['action' => 'index']);
        }

        // トークン発行（有効期限1時間）
        $token = Text::uuid() . bin2hex(random_bytes(16));
        $user->email_change_token = $token;
        $user->email_change_expires = new FrozenTime('+1 hour');

        if ($this->Users->save($user)) {
            // 確認メール送信（UserMailer）
            /** @var \App\Mailer\UserMailer $mailer */
            $mailer = $this->getMailer('User');
            $mailer->send('emailChangeConfirm', [$user, $token]);

            // 旧メールにも通知（任意）
            if (!empty($user->email)) {
                $mailer->send('emailChangeNoticeOld', [$user]);
            }

            $this->Flash->success('確認メールを送信しました。メール内リンクで変更を完了してください。');
        } else {
            $this->Flash->error('メール変更の処理に失敗しました。');
        }

        return $this->redirect(['action' => 'index']);
    }

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
            return $this->redirect(['action' => 'index']);
        }

        if ($newPassword !== $newPassword2) {
            $this->Flash->error('新しいパスワードが一致しません。');
            return $this->redirect(['action' => 'index']);
        }

        $user = $this->Users->patchEntity($user, ['password' => $newPassword], [
            'validate' => 'passwordChange'
        ]);
        if ($user->getErrors()) {
            $this->Flash->error('パスワードがポリシーを満たしていません。');
            return $this->redirect(['action' => 'index']);
        }

        if ($this->Users->save($user)) {
            // セッション再生成（推奨）
            $this->request->getSession()->renew();
            $this->Flash->success('パスワードを変更しました。');
        } else {
            $this->Flash->error('変更に失敗しました。');
        }

        return $this->redirect(['action' => 'index']);
    }
}
