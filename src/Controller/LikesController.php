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

        // 👇 すでに同じ user_id × portfolio_id のレコードがあるか確認
        $existingLike = $this->Likes->find()
            ->where([
                'user_id' => $user->get('id'),
                'portfolio_id' => $portfolioId
            ])
            ->first();

        if ($existingLike) {
            $this->Flash->error('すでにいいねしています');
            return $this->redirect($this->referer());
        }

        // 新しいいいねを作成
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

    
}
