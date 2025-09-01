<?php

// src/Controller/Admin/CompaniesController.php
namespace App\Controller\Admin;

class CompaniesController extends AppController
{
    public function index()
    {
        $q = $this->request->getQuery();
        $Companies = $this->fetchTable('Companies');

        $query = $Companies->find()->order(['Companies.modified'=>'DESC']);

        if (!empty($q['verified'])) {
            $query->where(['Companies.verified' => (int)$q['verified']]);
        }
        if (!empty($q['plan'])) {
            $query->where(['Companies.plan' => $q['plan']]);
        }
        if (!empty($q['q'])) {
            $kw = '%' . str_replace('%','\%',$q['q']) . '%';
            $query->where(['OR' => [
                'Companies.name LIKE' => $kw,
                'Companies.domain LIKE' => $kw,
            ]]);
        }

        $this->paginate = ['limit'=>20];
        $companies = $this->paginate($query);
        $this->set(compact('companies','q'));
    }

    public function verify($id)
    {
        $this->request->allowMethod(['post']);
        $co = $this->fetchTable('Companies')->get($id);
        $co->verified = 1;
        $this->fetchTable('Companies')->save($co);
        $this->Flash->success('Verifiedにしました。');
        return $this->redirect($this->referer());
    }

    public function plan($id, $plan)
    {
        $this->request->allowMethod(['post']);
        $co = $this->fetchTable('Companies')->get($id);
        $co->plan = $plan; // 'free'|'pro'|'enterprise'
        $this->fetchTable('Companies')->save($co);
        $this->Flash->success('プランを更新しました。');
        return $this->redirect($this->referer());
    }
}
