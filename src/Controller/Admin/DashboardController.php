<?php
namespace App\Controller\Admin;

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
