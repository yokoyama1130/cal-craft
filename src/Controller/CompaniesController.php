<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Utility\Text;

/**
 * Companies Controller
 *
 * @property \App\Model\Table\CompaniesTable $Companies
 * @method \App\Model\Entity\Company[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CompaniesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function add()
    {
        $user = $this->request->getAttribute('identity');
        $userId = $user->getIdentifier();
    
        // 既に自分の会社があれば編集へ
        $existing = $this->Companies->find()->where(['owner_user_id' => $userId])->first();
        if ($existing) {
            $this->Flash->info(__('You already have a company profile.'));
            return $this->redirect(['action' => 'edit', $existing->id]);
        }
    
        $company = $this->Companies->newEmptyEntity();
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            if (empty($data['slug']) && !empty($data['name'])) {
                $data['slug'] = Text::slug($data['name']);
            }
            $data['owner_user_id'] = $userId;
    
            $company = $this->Companies->patchEntity($company, $data);
            if ($this->Companies->save($company)) {
                $this->Flash->success(__('Company has been created.'));
                return $this->redirect(['action' => 'view', $company->id]);
            }
            $this->Flash->error(__('Unable to create company.'));
        }
        $this->set(compact('company'));
    }
    
    public function edit($id = null)
    {
        $userId = $this->request->getAttribute('identity')->getIdentifier();
        $company = $this->Companies->get($id);
    
        if ($company->owner_user_id !== (int)$userId) {
            $this->Flash->error(__('Not authorized.'));
            return $this->redirect(['action' => 'view', $id]);
        }
    
        if ($this->request->is(['patch','post','put'])) {
            $data = $this->request->getData();
            if (empty($data['slug']) && !empty($data['name'])) {
                $data['slug'] = Text::slug($data['name']);
            }
            $company = $this->Companies->patchEntity($company, $data);
            if ($this->Companies->save($company)) {
                $this->Flash->success(__('Company updated.'));
                return $this->redirect(['action' => 'view', $company->id]);
            }
            $this->Flash->error(__('Update failed.'));
        }
        $this->set(compact('company'));
    }
}
