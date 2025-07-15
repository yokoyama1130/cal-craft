<?php
declare(strict_types=1);

namespace App\Controller;

class NotificationsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authentication');
        $this->loadModel('Notifications');
    }

    public function index()
    {
        $this->loadModel('Notifications');

        $userId = $this->request->getAttribute('identity')->get('id');

        $notifications = $this->Notifications->find()
            ->where(['user_id' => $userId])
            ->order(['created' => 'DESC'])
            ->limit(50)
            ->toArray();

        $this->set(compact('notifications'));

        $this->Notifications->updateAll(
            ['is_read' => true],
            ['user_id' => $userId, 'is_read' => false]
        );
    }

    public function markAsRead($id)
    {
        $notification = $this->Notifications->get($id);
        $notification->is_read = true;
        $this->Notifications->save($notification);

        return $this->redirect($this->referer());
    }

    public function read($id)
    {
        $notification = $this->Notifications->get($id);
        if ($notification->user_id !== $this->request->getAttribute('identity')->get('id')) {
            throw new ForbiddenException();
        }
        $notification->is_read = true;
        $this->Notifications->save($notification);

        // 通知に紐づいたポートフォリオへ遷移
        return $this->redirect(['controller' => 'Portfolios', 'action' => 'view', $notification->portfolio_id]);
    }

}
