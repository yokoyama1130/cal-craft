<?php
declare(strict_types=1);

namespace App\Controller;

class FollowsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authentication.Authentication');
        $this->loadModel('Follows');
        $this->loadModel('Notifications'); // ✅ 通知用モデルも読み込み
    }

    // フォロー処理
    public function follow($userId)
    {
        $followerId = $this->request->getAttribute('identity')->get('id');

        if ($followerId == $userId) {
            return $this->redirect($this->referer());
        }

        // すでにフォローしているか確認
        $exists = $this->Follows->exists([
            'follower_id' => $followerId,
            'followed_id' => $userId
        ]);

        if (!$exists) {
            // フォローデータ保存
            $follow = $this->Follows->newEntity([
                'follower_id' => $followerId,
                'followed_id' => $userId
            ]);
            $this->Follows->save($follow);

            // ✅ 通知データ作成
            $notification = $this->Notifications->newEntity([
                'user_id' => $userId,              // 通知の受け取り手（フォローされた人）
                'sender_id' => $followerId,        // フォローした人
                'type' => 'follow',
                'is_read' => false
            ]);
            $this->Notifications->save($notification);
        }

        return $this->redirect($this->referer());
    }

    // フォロー解除処理
    public function unfollow($userId)
    {
        $followerId = $this->request->getAttribute('identity')->get('id');

        $follow = $this->Follows->find()
            ->where([
                'follower_id' => $followerId,
                'followed_id' => $userId
            ])
            ->first();

        if ($follow) {
            $this->Follows->delete($follow);
        }

        return $this->redirect($this->referer());
    }
}
