<?php
declare(strict_types=1);

// src/Controller/FollowsController.php
namespace App\Controller;

class FollowsController extends AppController
{
    public function follow($id)
    {
        $this->request->allowMethod(['post']);
        $this->loadModel('Followers');

        $followerId = $this->request->getAttribute('identity')->get('id');
        $followeeId = (int)$id;

        if ($followerId === $followeeId) {
            $this->Flash->error('自分自身をフォローできません。');
            return $this->redirect($this->referer());
        }

        $exists = $this->Followers->exists([
            'follower_id' => $followerId,
            'followee_id' => $followeeId
        ]);

        if (!$exists) {
            $f = $this->Followers->newEntity([
                'follower_id' => $followerId,
                'followee_id' => $followeeId
            ]);
            $this->Followers->save($f);
        }

        return $this->redirect($this->referer());
    }

    public function unfollow($id)
    {
        $this->request->allowMethod(['post']);
        $this->loadModel('Followers');

        $followerId = $this->request->getAttribute('identity')->get('id');
        $followeeId = (int)$id;

        $entity = $this->Followers->find()
            ->where([
                'follower_id' => $followerId,
                'followee_id' => $followeeId
            ])
            ->first();

        if ($entity) {
            $this->Followers->delete($entity);
        }

        return $this->redirect($this->referer());
    }
}
