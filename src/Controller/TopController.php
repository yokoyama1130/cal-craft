<?php
declare(strict_types=1);

namespace App\Controller;

class TopController extends AppController
{
    public function index()
    {
        $this->loadModel('Portfolios');
        $portfolios = $this->Portfolios->find()
            ->where(['is_public' => true])
            ->order(['created' => 'DESC'])
            ->limit(10);
    
        $this->set(compact('portfolios'));
    }    
}
