<?php
declare(strict_types=1);

namespace App\Controller;

class NotificationsController extends AppController
{
    /**
     * 通知一覧の表示
     *
     * - ログイン中ユーザーの通知を取得し、最新順に一覧表示
     * - 一覧画面に遷移したタイミングで未読通知を既読状態に更新
     *
     * @return void
     */
    public function index()
    {
        $this->Notifications = $this->fetchTable('Notifications');

        $userId = $this->request->getAttribute('identity')->get('id');

        $notifications = $this->Notifications->find()
            ->where(['user_id' => $userId])
            ->contain(['SenderUsers']) // ← ここ追加
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
