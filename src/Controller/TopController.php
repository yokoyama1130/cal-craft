<?php
declare(strict_types=1);

namespace App\Controller;

class TopController extends AppController
{
    public function index()
    {
        $this->loadModel('Portfolios');
        $portfolios = $this->Portfolios
            ->find()
            ->contain(['Users'])
            // ↓↓↓↓↓↓後でコメントアウト解除する↓↓↓↓↓↓
            // ->order(['created' => 'DESC']) // or rand()
            ->limit(6)
            ->all();
    
        $this->set(compact('portfolios'));
    }
    
}
