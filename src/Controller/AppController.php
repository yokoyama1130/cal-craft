<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;

class AppController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');
    }

    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        $identity = $this->request->getAttribute('identity');

        if ($identity) {
            $this->loadModel('Notifications');
            $userId = $identity->get('id');

            $unreadCount = $this->Notifications->find()
                ->where(['user_id' => $userId, 'is_read' => false])
                ->count();

            $this->set('unreadCount', $unreadCount);
        }
    }

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
                'fields'   => ['username' => 'auth_email', 'password' => 'auth_password'],
            ]);
        } else {
            // 既存の一般ユーザー側
            $service->loadIdentifier('Authentication.Orm', [
                'userModel' => 'Users',
                'fields' => ['username' => 'email', 'password' => 'password'],
            ]);
            $service->loadAuthenticator('Authentication.Form', [
                'loginUrl' => '/users/login',
                'fields'   => ['username' => 'email', 'password' => 'password'],
            ]);
        }

        return $service;
    }
}