<?php
declare(strict_types=1);

namespace App\Controller;

class FollowsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authentication.Authentication');
    }

    // フォロー処理
    public function follow($userId)
    {
        $followerId = $this->request->getAttribute('identity')->get('id');

        if ($followerId == $userId) {
            return $this->redirect($this->referer());
        }

        $this->loadModel('Follows');
        $exists = $this->Follows->exists([
            'follower_id' => $followerId,
            'followed_id' => $userId // ✅ 修正ポイント
        ]);

        if (!$exists) {
            $follow = $this->Follows->newEntity([
                'follower_id' => $followerId,
                'followed_id' => $userId // ✅ 修正ポイント
            ]);
            $this->Follows->save($follow);
        }

        return $this->redirect($this->referer());
    }

    // フォロー解除処理
    public function unfollow($userId)
    {
        $followerId = $this->request->getAttribute('identity')->get('id');

        $this->loadModel('Follows');
        $follow = $this->Follows->find()
            ->where([
                'follower_id' => $followerId,
                'followed_id' => $userId // ✅ 修正ポイント
            ])
            ->first();

        if ($follow) {
            $this->Follows->delete($follow);
        }

        return $this->redirect($this->referer());
    }
}
