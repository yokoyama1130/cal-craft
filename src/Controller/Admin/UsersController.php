<?php

namespace App\Controller\Admin;

use App\Controller\AppController;

class UsersController extends AppController
{
    public function index()
    {
        $this->paginate = ['limit' => 20];
        $users = $this->paginate($this->Users->find());
        $this->set(compact('users'));
    }
}
