<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;

class LikesController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Authentication');
        $this->loadModel('Likes'); // Likes テーブルを明示的にロード
        // $this->loadModel('Portfolios'); ← 必要なければ消してOK
    }

    public function add()
    {
        $this->request->allowMethod(['post']);
    
        $user = $this->Authentication->getIdentity();
        $portfolioId = $this->request->getData('portfolio_id');
    
        // すでに同じ user_id × portfolio_id のレコードがあるか確認
        $existingLike = $this->Likes->find()
            ->where([
                'user_id' => $user->get('id'),
                'portfolio_id' => $portfolioId
            ])
            ->first();
    
        if ($existingLike) {
            // 👇 すでにいいねしてる場合は削除（トグル）
            $this->Likes->delete($existingLike);
            return $this->redirect($this->referer());
        }
    
        // いいねしてない場合は追加
        $like = $this->Likes->newEmptyEntity();
        $like = $this->Likes->patchEntity($like, [
            'user_id' => $user->get('id'),
            'portfolio_id' => $portfolioId
        ]);
    
        if ($this->Likes->save($like)) {
            return $this->redirect($this->referer());
        }
    
        $this->Flash->error('いいねできませんでした');
        return $this->redirect($this->referer());
    }

    public function toggle()
    {
        $this->request->allowMethod(['post']);
        $this->autoRender = false;

        $this->loadModel('Likes');
        $user = $this->Authentication->getIdentity();
        $userId = $user->get('id');
        $portfolioId = $this->request->getData('portfolio_id');

        // 既にいいね済みか確認
        $existingLike = $this->Likes->find()
            ->where(['user_id' => $userId, 'portfolio_id' => $portfolioId])
            ->first();

        if ($existingLike) {
            $this->Likes->delete($existingLike);
            $liked = false;
        } else {
            $like = $this->Likes->newEntity([
                'user_id' => $userId,
                'portfolio_id' => $portfolioId
            ]);
            $this->Likes->save($like);
            $liked = true;
        }

        $likeCount = $this->Likes->find()
            ->where(['portfolio_id' => $portfolioId])
            ->count();

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'liked' => $liked,
                'likeCount' => $likeCount
            ]));
    }

}
