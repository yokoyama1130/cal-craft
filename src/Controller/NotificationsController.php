<?php
declare(strict_types=1);

namespace App\Controller;

class NotificationsController extends AppController
{
    public function index()
    {
        $this->loadModel('Notifications');

        $userId = $this->request->getAttribute('identity')->get('id');

        $notifications = $this->Notifications->find()
            ->where(['user_id' => $userId])
            ->order(['created' => 'DESC'])
            ->toArray();

        $this->set(compact('notifications'));

        // 一覧を表示したら未読を既読に更新
        $this->Notifications->updateAll(
            ['is_read' => true],
            ['user_id' => $userId, 'is_read' => false]
        );
    }
}
