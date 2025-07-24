<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController as BaseController;

class AppController extends BaseController
{
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        $identity = $this->Authentication->getIdentity();

        if (!$identity || $identity->get('email') !== 'yokoyama.ryogo.1130@gmail.com') {
            $this->Flash->error('管理者専用ページです');
            return $this->redirect('/');
        }

        // 管理画面専用レイアウト適用
        $this->viewBuilder()->setLayout('admin');
    }
}
