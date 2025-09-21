<?php
declare(strict_types=1);

namespace App\Controller\Employer;

use App\Controller\AppController;
use Cake\Event\EventInterface;          // ★ これを追加
use Cake\Mailer\Mailer;                // 使ってるので追加
use Cake\Routing\Router;               // 使ってるので追加
use Cake\Utility\Text;                 // 使ってるので追加

class AuthController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->allowUnauthenticated(['login', 'verifyEmail', 'resendVerification']); // 必要分
        $this->loadModel('Companies'); // ★ これがないと $this->Companies が未定義
    }

    public function beforeFilter(EventInterface $event) // ★ 型は Cake\Event\EventInterface
    {
        parent::beforeFilter($event);
        // Employer配下でも未ログインで叩けるようにするならここでも可
        $this->Authentication->allowUnauthenticated(['login', 'verifyEmail', 'resendVerification']);
    }

    public function verifyEmail($token = null)
    {
        $company = $this->Companies->find()->where(['email_token' => $token])->first();

        if (!$company) {
            $this->Flash->error('無効な認証リンクです。');
            return $this->redirect('/employer/login');
        }

        $company->email_verified = true;
        $company->email_token = null;

        if ($this->Companies->save($company)) {
            $this->Flash->success('企業メールの認証が完了しました。ログインできます。');
            return $this->redirect('/employer/login?auth_email=' . urlencode($company->auth_email));
        }

        $this->Flash->error('認証処理中にエラーが発生しました。');
        return $this->redirect('/employer/login');
    }

    public function resendVerification()
    {
        if ($this->request->is('post')) {
            $email = trim((string)$this->request->getData('email'));
            $company = $this->Companies->find()->where(['auth_email' => $email])->first();

            if (!$company) {
                $this->Flash->error('該当するメールアドレスが見つかりません。');
                return $this->redirect(['action' => 'resendVerification']);
            }
            if ($company->email_verified) {
                $this->Flash->success('既に認証済みです。ログインしてください。');
                return $this->redirect('/employer/login?auth_email=' . urlencode($company->auth_email));
            }

            $company->email_token = Text::uuid();
            if ($this->Companies->save($company)) {
                $mailer = new Mailer('default');
                $mailer->setTo($company->auth_email)
                    ->setSubject('【OrcaFolio】企業メール認証の再送')
                    ->deliver(
                        "以下のURLから認証を完了してください：\n\n" .
                        Router::url(
                            [
                                'prefix' => 'Employer',           // ★ このコントローラに飛ばすなら prefix を明示
                                'controller' => 'Auth',
                                'action' => 'verifyEmail',
                                $company->email_token
                            ],
                            true
                        )
                    );

                $this->Flash->success('認証メールを再送しました。メールをご確認ください。');
                return $this->redirect('/employer/login?auth_email=' . urlencode($company->auth_email));
            }

            $this->Flash->error('再送に失敗しました。時間をおいてお試しください。');
        }
        // GET の場合は再送フォームを表示（省略可）
    }

    public function login()
    {
        $this->request->allowMethod(['get', 'post']);
        $result = $this->Authentication->getResult();

        if ($result->isValid()) {
            $identity = $this->Authentication->getIdentity();
            $companyId = $identity->get('id');

            $company = $this->Companies->get($companyId);
            if (!$company->email_verified) {
                $this->Authentication->logout();
                $this->Flash->error('メール認証が未完了です。メールのリンクを確認するか、認証メールを再送してください。');

                return $this->redirect([
                    'prefix' => 'Employer',
                    'controller' => 'Auth',
                    'action' => 'resendVerification'
                ]);
            }

            return $this->redirect([
                'prefix' => false,
                'controller' => 'Companies',
                'action' => 'view',
                $company->id
            ]);
        }

        if ($this->request->is('post')) {
            $this->Flash->error('ログインに失敗しました');
        }
    }

    public function logout()
    {
        $this->Authentication->logout();
        return $this->redirect('/employer/login');
    }
}
