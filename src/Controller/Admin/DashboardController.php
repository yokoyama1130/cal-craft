<?php

namespace App\Controller\Admin;

use App\Controller\AppController;

class DashboardController extends AppController
{
    public function index()
    {
        $this->loadModel('Users');
        $this->loadModel('Portfolios');

        $userCount = $this->Users->find()->count();
        $portfolioCount = $this->Portfolios->find()->count();

        $this->set(compact('userCount', 'portfolioCount'));
    }
}
