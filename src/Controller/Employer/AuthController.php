<?php
declare(strict_types=1);
// src/Controller/Employer/AuthController.php
namespace App\Controller\Employer;

use App\Controller\AppController;
use Authentication\PasswordHasher\DefaultPasswordHasher;

class AuthController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->allowUnauthenticated(['login']);
    }

    public function login()
    {
        $result = $this->Authentication->getResult();
        if ($result->isValid()) {
            return $this->redirect($this->request->getQuery('redirect', '/employer/companies/edit'));
        }
    
        if ($this->request->is('post')) {
            $email = (string)$this->request->getData('auth_email');
            $plain = (string)$this->request->getData('auth_password');
    
            $Companies = $this->fetchTable('Companies');
            $row = $Companies->find()
                ->select(['id','auth_email','auth_password'])
                ->where(['auth_email' => $email])
                ->first();
    
            if (!$row) {
                $this->Flash->error('Invalid email or password (email not found).');
            } else {
                $hash = (string)$row->auth_password;
                $ok = (new DefaultPasswordHasher())->check($plain, $hash);
                $this->Flash->error(
                    'Invalid email or password (found email; bcrypt? ' .
                    (preg_match('/^\$2y\$/', $hash) ? 'yes' : 'no') .
                    '; password match? ' . ($ok ? 'yes' : 'no') . ').'
                );
            }
        }
    }

    public function logout()
    {
        $this->Authentication->logout();
        return $this->redirect('/employer/login');
    }
}