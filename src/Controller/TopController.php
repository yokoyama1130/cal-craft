<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;

class TopController extends AppController
{
    // src/Controller/PortfoliosController.php

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        // indexアクションだけログイン不要にする
        $this->Authentication->addUnauthenticatedActions(['index']);
    }

    public function index()
    {
        $this->loadModel('Likes');
        $this->loadModel('Portfolios');
    
        $identity = $this->request->getAttribute('identity');
        $actor = [];
        if ($identity) {
            $id = (int)$identity->get('id');
            // Users or Companies?
            $this->loadModel('Users');
            if ($this->Users->exists(['id' => $id])) {
                $actor = ['user_id' => $id];
            } else {
                $this->loadModel('Companies');
                if ($this->Companies->exists(['id' => $id])) {
                    $actor = ['company_id' => $id];
                }
            }
        }
    
        $portfolios = $this->Portfolios->find()
            ->contain(['Users'])
            ->where(['is_public' => true])
            ->order(['created' => 'DESC'])
            ->limit(10)
            ->toArray();
    
        foreach ($portfolios as $p) {
            $p->like_count = $this->Likes->find()
                ->where(['portfolio_id' => $p->id])
                ->count();
    
            $p->liked_by_me = false;
            if ($actor) {
                $p->liked_by_me = $this->Likes->exists(array_merge(['portfolio_id' => $p->id], $actor));
            }
        }
    
        $this->set(compact('portfolios'));
    }    

}
