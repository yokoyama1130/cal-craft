<?php

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;

class UsersController extends AppController
{
    public function index()
    {
        $this->loadModel('Users'); // 🔸 loadModel を忘れずに！
        $this->paginate = ['limit' => 20];
        $users = $this->paginate($this->Users->find());
        $this->set(compact('users'));
    }
}
