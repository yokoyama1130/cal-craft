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
    
        $like = $this->Likes->newEmptyEntity();
        $like = $this->Likes->patchEntity($like, [
            'user_id' => $user->get('id'),
            'portfolio_id' => $this->request->getData('portfolio_id')
        ]);
    
        if ($this->Likes->save($like)) {
            // 成功時にTop/indexへリダイレクト（または $this->referer() でもOK）
            return $this->redirect(['controller' => 'Top', 'action' => 'index']);
        }
    
        $this->Flash->error('いいねできませんでした');
        return $this->redirect(['controller' => 'Top', 'action' => 'index']);
    }
    
}
