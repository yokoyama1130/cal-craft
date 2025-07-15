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

        // いいね成功後に通知
        if ($this->Likes->save($like)) {
            // 通知を作成（自分自身へのいいねは除外）
            if ($user->get('id') !== $portfolio->user_id) {
                $this->loadModel('Notifications');
                $notification = $this->Notifications->newEmptyEntity();
                $notification->user_id = $portfolio->user_id; // 通知を受け取る人
                $notification->sender_id = $user->get('id'); // 通知を送った人
                $notification->portfolio_id = $portfolioId;
                $notification->type = 'like';
                $this->Notifications->save($notification);
            }

            return $this->redirect($this->referer());
        }

    }

    public function toggle()
    {
        $this->request->allowMethod(['post']);
        $this->loadModel('Likes');
        $this->loadModel('Notifications');
        $this->loadModel('Portfolios');
    
        $userId = $this->request->getAttribute('identity')->get('id');
        $portfolioId = $this->request->getData('portfolio_id');
    
        $existing = $this->Likes->find()
            ->where(['user_id' => $userId, 'portfolio_id' => $portfolioId])
            ->first();
    
        $liked = false;
    
        if ($existing) {
            // いいね解除
            $this->Likes->delete($existing);
        } else {
            // いいね登録
            $like = $this->Likes->newEntity([
                'user_id' => $userId,
                'portfolio_id' => $portfolioId,
            ]);
            $this->Likes->save($like);
            $liked = true;
    
            // 通知を送る（自分以外にのみ）
            $portfolio = $this->Portfolios->get($portfolioId);
            if ($portfolio->user_id !== $userId) {
                $notification = $this->Notifications->newEntity([
                    'user_id' => $portfolio->user_id,   // 通知を受け取る人
                    'sender_id' => $userId,             // 通知を送った人
                    'portfolio_id' => $portfolio->id,
                    'type' => 'like',
                    'is_read' => false,
                ]);
                $this->Notifications->save($notification);
            }
        }
    
        // 最新のいいね数
        $likeCount = $this->Likes->find()
            ->where(['portfolio_id' => $portfolioId])
            ->count();
    
        return $this->response->withType('application/json')->withStringBody(json_encode([
            'success' => true,
            'liked' => $liked,
            'likeCount' => $likeCount,
        ]));
    }
    
}
