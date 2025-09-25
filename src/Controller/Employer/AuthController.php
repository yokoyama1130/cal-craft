<?php
declare(strict_types=1);

namespace App\Controller\Employer;

use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\Mailer\Mailer;
use Cake\Routing\Router;
use Cake\Utility\Text;

class AuthController extends AppController
{
    /**
     * コントローラの初期化処理。
     *
     * - 認証不要アクションを設定（login, verifyEmail, resendVerification）
     * - Companies テーブルをロード
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->allowUnauthenticated(['login', 'verifyEmail', 'resendVerification']); // 必要分
        $this->Companies = $this->fetchTable('Companies');
    }

    /**
     * アクション実行前の共通処理。
     *
     * - 親クラスの beforeFilter を実行
     * - 認証不要アクションを設定（login, verifyEmail, resendVerification）
     *
     * @param \Cake\Event\EventInterface $event イベントオブジェクト
     * @return void
     */
    public function beforeFilter(EventInterface $event) // ★ 型は Cake\Event\EventInterface
    {
        parent::beforeFilter($event);
        // Employer配下でも未ログインで叩けるようにするならここでも可
        $this->Authentication->allowUnauthenticated(['login', 'verifyEmail', 'resendVerification']);
    }

    /**
     * 企業ユーザーのメールアドレス認証処理。
     *
     * メールリンクに含まれるトークンを検証し、対応する企業の
     * email_verified を true に設定して保存する。
     *
     * - トークン不正 → エラーメッセージを表示し、ログイン画面へリダイレクト
     * - 成功 → 成功メッセージを表示し、ログイン画面（メール入力済み）へリダイレクト
     * - 保存失敗 → エラーメッセージを表示し、ログイン画面へリダイレクト
     *
     * @param string|null $token メール認証用トークン
     * @return \Cake\Http\Response|null リダイレクトレスポンス
     */
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

    /**
     * 企業メール認証リンクの再送処理。
     *
     * - POST の場合：入力されたメールを確認し、認証未完了なら新しいトークンを発行して
     *   認証メールを再送信する。
     *   - 該当ユーザーなし → エラーメッセージを表示し再送フォームへ戻す
     *   - 既に認証済み → 成功メッセージを表示しログイン画面へリダイレクト
     *   - 再送成功 → 成功メッセージを表示しログイン画面へリダイレクト
     *   - 保存または送信失敗 → エラーメッセージを表示
     * - GET の場合：再送フォームを表示
     *
     * @return \Cake\Http\Response|null リダイレクトレスポンス または null
     */
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
                                'prefix' => 'Employer', // ★ このコントローラに飛ばすなら prefix を明示
                                'controller' => 'Auth',
                                'action' => 'verifyEmail',
                                $company->email_token,
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

    /**
     * 企業ユーザーのログイン処理。
     *
     * - GET: ログインフォームを表示
     * - POST: 認証を試み、成功時には Companies/view へリダイレクト
     *   - ただしメール未認証の場合はログアウトさせ、再認証を促す
     * - 認証失敗時: エラーメッセージを表示
     *
     * @return \Cake\Http\Response|null ログイン後のリダイレクトレスポンス または null
     */
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
                    'action' => 'resendVerification',
                ]);
            }

            return $this->redirect([
                'prefix' => false,
                'controller' => 'Companies',
                'action' => 'view',
                $company->id,
            ]);
        }

        if ($this->request->is('post')) {
            $this->Flash->error('ログインに失敗しました');
        }
    }

    /**
     * 企業ユーザーのログアウト処理。
     *
     * - セッションからログイン情報を破棄
     * - ログインページへリダイレクト
     *
     * @return \Cake\Http\Response ログイン画面へのリダイレクトレスポンス
     */
    public function logout()
    {
        $this->Authentication->logout();

        return $this->redirect('/employer/login');
    }
}
