<?php
namespace App\Controller\Admin;

use App\Controller\Admin\AppController;

class PortfoliosController extends AppController
{
    public function index()
    {
        $q = $this->request->getQueryParams();
        $Portfolios = $this->fetchTable('Portfolios');

        $query = $Portfolios->find()
            ->contain(['Users','Companies'])
            ->order(['Portfolios.created'=>'DESC']);

        if (!empty($q['q'])) {
            $kw = '%' . str_replace('%','\%',$q['q']) . '%';
            $query->where([
                'OR' => [
                    'Portfolios.title LIKE' => $kw,
                    'Portfolios.description LIKE' => $kw,
                ]
            ]);
        }
        if ($q['visibility'] ?? '' !== '') {
            $query->where(['Portfolios.is_public' => (int)$q['visibility']]);
        }
        if ($q['owner'] ?? '' === 'user') {
            $query->where(['Portfolios.user_id IS NOT' => null]);
        } elseif (($q['owner'] ?? '') === 'company') {
            $query->where(['Portfolios.company_id IS NOT' => null]);
        }

        $this->paginate = ['limit'=>20];
        $portfolios = $this->paginate($query);
        $this->set(compact('portfolios','q'));
    }

    public function toggle($id)
    {
        $this->request->allowMethod(['post']);
        $pf = $this->fetchTable('Portfolios')->get($id);
        $pf->is_public = (int)!$pf->is_public;
        $this->fetchTable('Portfolios')->save($pf);
        $this->Flash->success('公開状態を切り替えました。');
        return $this->redirect($this->referer());
    }

    public function delete($id)
    {
        $this->request->allowMethod(['post','delete']);
        $pf = $this->fetchTable('Portfolios')->get($id);
        $this->fetchTable('Portfolios')->delete($pf);
        $this->Flash->success('削除しました。');
        return $this->redirect($this->referer());
    }
}
