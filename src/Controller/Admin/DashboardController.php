<?php
declare(strict_types=1);

namespace App\Controller\Admin;

class DashboardController extends AppController
{
    /**
     * 管理ダッシュボードの統計情報を表示するアクション。
     *
     * - ユーザー数（削除されていないもの）
     * - 企業数
     * - ポートフォリオ数
     * - コメント数
     * - 未承認の企業数（verified != 1）
     * - 非公開ポートフォリオ数（is_public = 0）
     *
     * @return void
     */
    public function index()
    {
        $Users = $this->fetchTable('Users');
        $Companies = $this->fetchTable('Companies');
        $Portfolios = $this->fetchTable('Portfolios');
        $Comments = $this->fetchTable('Comments');

        $stats = [
            'users' => $Users->find()->where(['deleted_at IS' => null])->count(),
            'companies' => $Companies->find()->count(),
            'portfolios' => $Portfolios->find()->count(),
            'comments' => $Comments->find()->count(),
            'pendingCompanies' => $Companies->find()->where(['verified !=' => 1])->count(),
            'privatePortfolios' => $Portfolios->find()->where(['is_public' => 0])->count(),
        ];

        $this->set(compact('stats'));
        $this->viewBuilder()->setOption('serialize', []);
    }
}
