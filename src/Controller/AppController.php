<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;

class AppController extends Controller
{
    /**
     * コントローラ初期化処理
     *
     * コンポーネントの読み込みなどを行う。
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');
    }

    /**
     * コントローラのアクション実行前処理
     *
     * ログインユーザーの未読通知件数を取得し、
     * ビュー変数 `unreadCount` にセットします。
     *
     * @param \Cake\Event\EventInterface $event イベントオブジェクト
     * @return void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        $identity = $this->request->getAttribute('identity');

        if ($identity) {
            // $this->Notifications = $this->fetchTable('Notifications');

            $this->loadModel('Notifications');
            $userId = $identity->get('id');

            $unreadCount = $this->Notifications->find()
                ->where(['user_id' => $userId, 'is_read' => false])
                ->count();

            $this->set('unreadCount', $unreadCount);
        }
    }

    /**
     * 認証サービスの設定
     *
     * セッションおよびフォーム認証を利用し、プレフィックスによって
     * 一般ユーザー（Users）と企業ユーザー（Companies）の認証を切り替えます。
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request リクエストオブジェクト
     * @return \Authentication\AuthenticationServiceInterface 認証サービス
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $service = new AuthenticationService();

        $service->loadAuthenticator('Authentication.Session');

        $prefix = $request->getParam('prefix');

        if ($prefix === 'Employer') {
            // ★企業ログイン：Companies を使う
            $service->loadIdentifier('Authentication.Orm', [
                'userModel' => 'Companies',
                'fields' => ['username' => 'auth_email', 'password' => 'auth_password'],
            ]);
            $service->loadAuthenticator('Authentication.Form', [
                'loginUrl' => '/employer/login',
                'fields' => ['username' => 'auth_email', 'password' => 'auth_password'],
            ]);
        } else {
            // 既存の一般ユーザー側
            $service->loadIdentifier('Authentication.Orm', [
                'userModel' => 'Users',
                'fields' => ['username' => 'email', 'password' => 'password'],
            ]);
            $service->loadAuthenticator('Authentication.Form', [
                'loginUrl' => '/users/login',
                'fields' => ['username' => 'email', 'password' => 'password'],
            ]);
        }

        return $service;
    }

    // src/Controller/AppController.php に置くと便利
    // 「一般ユーザー」か「企業（Employer）」かで current actor を取り出す
    protected function getActor(): array
    {
        $idn = $this->request->getAttribute('identity');
        if (!$idn) return ['type'=>null, 'id'=>null];

        // 企業は auth_email を持ってるという前提
        if ($idn->get('auth_email') !== null) {
            return ['type' => 'company', 'id' => (int)$idn->get('id')];
        }
        return ['type' => 'user', 'id' => (int)$idn->get('id')];
    }
}