<?php
declare(strict_types=1);

namespace App\Controller;

use App\Mailer\UserMailer;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\I18n\FrozenTime;
use Cake\Utility\Text;

class SettingsController extends AppController
{
    /**
     * コントローラ初期化処理
     *
     * - confirmEmail アクションは未ログインでもアクセス可能に設定
     * - Users テーブルおよび Flash コンポーネントをロード
     * - CSRF 対策はミドルウェア想定（必要に応じて FormProtection を有効化）
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        // メール確認リンクは未ログインでも叩けるように
        if (method_exists($this->Authentication, 'addUnauthenticatedActions')) {
            $this->Authentication->addUnauthenticatedActions(['confirmEmail']);
        } else {
            $this->Authentication->allowUnauthenticated(['confirmEmail']);
        }

        $this->Users = $this->fetchTable('users');
        $this->loadComponent('Flash');
        // CSRF はミドルウェア想定。使っていなければ FormProtection を有効に
        // $this->loadComponent('FormProtection');
    }

    /**
     * ユーザー情報の表示
     *
     * - ログイン中のユーザーIDを取得し、Users テーブルから情報を取得
     * - 必須フィールド（id, email）は常に取得
     * - 任意フィールド（modified, password_changed_at）はカラムが存在する場合のみ追加
     * - ビューに $user を渡す
     *
     * @return void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException ユーザーが見つからない場合
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
     * メールアドレス編集画面の表示
     *
     * - 現在ログイン中のユーザーIDを取得し、id・email・new_email フィールドを取得
     * - ビューに $user をセットして edit_email テンプレートを表示
     *
     * @return void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException ユーザーが見つからない場合
     */
    public function editEmail()
    {
        $identity = $this->request->getAttribute('identity');
        $user = $this->Users->get($identity->getIdentifier(), [
            'fields' => ['id', 'email', 'new_email'],
        ]);
        $this->set(compact('user'));
        $this->render('edit_email'); // templates/Settings/edit_email.php
    }

    /**
     * パスワード編集画面の表示
     *
     * - 認証中ユーザー向けにパスワード変更フォームを表示
     * - templates/Settings/edit_password.php を利用
     *
     * @return void
     */
    public function editPassword()
    {
        $this->render('edit_password'); // templates/Settings/edit_password.php
    }

    /**
     * メールアドレス更新処理
     *
     * - POST専用
     * - 現在のパスワードを確認し、新しいメールアドレスを一時的に保存
     * - トークン付きの確認メールを送信し、リンククリックで変更を完了するフロー
     * - 連続リクエストは60秒間隔で制限
     *
     * @return \Cake\Http\Response|null リダイレクトレスポンス
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
            'validate' => 'emailChange',
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
     * メールアドレス変更の確認
     *
     * - 確認メール内のトークンを受け取り、新しいメールアドレスに反映する
     * - トークンが存在しない／無効／期限切れの場合はエラーを表示
     * - 成功時は Users テーブルの email を更新し、new_email / token / 有効期限をクリアする
     *
     * @param string|null $token メール認証用トークン
     * @return \Cake\Http\Response|null リダイレクトレスポンス
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
                'email_change_expires >' => FrozenTime::now(),
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

    // src/Controller/SettingsController.php
    public function deleteConfirm()
    {
        // 確認画面（フォームだけ出す）
        // ビュー: templates/Settings/delete_confirm.php
    }

    public function deleteAccount()
    {
        $this->request->allowMethod(['post']);

        $identity = $this->request->getAttribute('identity');
        $user = $this->Users->get($identity->getIdentifier());

        // 再認証：現在パスワード
        $hasher = new \Authentication\PasswordHasher\DefaultPasswordHasher();
        $currentPassword = (string)$this->request->getData('current_password');
        if (!$hasher->check($currentPassword, (string)$user->password)) {
            $this->Flash->error('現在のパスワードが違います。');
            return $this->redirect(['action' => 'deleteConfirm']);
        }

        // 確認キーワード
        $confirm = (string)$this->request->getData('confirm_keyword');
        if (strtoupper(trim($confirm)) !== 'DELETE') {
            $this->Flash->error('確認キーワードが一致しません。DELETE と入力してください。');
            return $this->redirect(['action' => 'deleteConfirm']);
        }

        // ソフトデリート＋匿名化
        $user->deleted_at = new \Cake\I18n\FrozenTime();
        $user->email = sprintf('deleted+%d@invalid.example', $user->id);
        if ($this->Users->getSchema()->hasColumn('new_email')) $user->set('new_email', null);
        if ($this->Users->getSchema()->hasColumn('email_change_token')) $user->set('email_change_token', null);
        if ($this->Users->getSchema()->hasColumn('email_change_expires')) $user->set('email_change_expires', null);
        $user->password = bin2hex(random_bytes(16)); // 再ログイン不能に

        if ($this->Users->save($user)) {
            // ログアウト
            $this->Authentication->logout();
            $this->request->getSession()->destroy();
            $this->Flash->success('アカウントを削除（無効化）しました。');
            return $this->redirect(['controller' => 'Pages', 'action' => 'display', 'home']);
        }

        $this->Flash->error('削除に失敗しました。時間をおいて再度お試しください。');
        return $this->redirect(['action' => 'deleteConfirm']);
    }
}
