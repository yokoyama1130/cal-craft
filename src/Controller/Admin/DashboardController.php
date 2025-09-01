<?php
namespace App\Controller\Admin;

class DashboardController extends AppController
{
    public function index()
    {
        $Users = $this->fetchTable('Users');
        $Companies = $this->fetchTable('Companies');
        $Portfolios = $this->fetchTable('Portfolios');
        $Comments = $this->fetchTable('Comments');

        $stats = [
            'users'      => $Users->find()->where(['deleted_at IS' => null])->count(),
            'companies'  => $Companies->find()->count(),
            'portfolios' => $Portfolios->find()->count(),
            'comments'   => $Comments->find()->count(),
            'pendingCompanies' => $Companies->find()->where(['verified !=' => 1])->count(),
            'privatePortfolios' => $Portfolios->find()->where(['is_public' => 0])->count(),
        ];

        $this->set(compact('stats'));
        $this->viewBuilder()->setOption('serialize', []);
    }
}
