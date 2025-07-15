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
}

