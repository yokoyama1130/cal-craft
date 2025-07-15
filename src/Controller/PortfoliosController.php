<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Portfolios Controller
 *
 * @property \App\Model\Table\PortfoliosTable $Portfolios
 * @method \App\Model\Entity\Portfolio[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class PortfoliosController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['Users'],
        ];
        $portfolios = $this->paginate($this->Portfolios);

        $this->set(compact('portfolios'));
    }

    /**
     * View method
     *
     * @param string|null $id Portfolio id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $portfolio = $this->Portfolios->get($id, [
            'contain' => ['Users'],
        ]);

        $this->set(compact('portfolio'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $portfolio = $this->Portfolios->newEmptyEntity();
    
        if ($this->request->is('post')) {
            $portfolio = $this->Portfolios->patchEntity($portfolio, $this->request->getData());
    
            // 👇 ログインユーザーの user_id を自動でセット
            $portfolio->user_id = $this->request->getAttribute('identity')->get('id');
    
            if ($this->Portfolios->save($portfolio)) {
                $this->Flash->success('投稿が完了しました！');
                return $this->redirect(['controller' => 'Top', 'action' => 'index']);
            }
            $this->Flash->error('投稿に失敗しました。もう一度お試しください。');
        }
    
        $this->set(compact('portfolio'));
    }
    

    /**
     * Edit method
     *
     * @param string|null $id Portfolio id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id)
    {
        $portfolio = $this->Portfolios->get($id);
    
        // 自分の投稿のみ編集可
        if ($portfolio->user_id !== $this->request->getAttribute('identity')->get('id')) {
            throw new ForbiddenException();
        }
    
        if ($this->request->is(['post', 'put'])) {
            $this->Portfolios->patchEntity($portfolio, $this->request->getData());
            if ($this->Portfolios->save($portfolio)) {
                $this->Flash->success('更新しました');
                return $this->redirect(['controller' => 'Users', 'action' => 'profile']);
            }
            $this->Flash->error('更新に失敗しました');
        }
    
        $this->set(compact('portfolio'));
    }
    

    /**
     * Delete method
     *
     * @param string|null $id Portfolio id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id)
    {
        $portfolio = $this->Portfolios->get($id);
        $userId = $this->request->getAttribute('identity')->get('id');
    
        // 他人の投稿は削除させない
        if ($portfolio->user_id !== $userId) {
            throw new \Cake\Http\Exception\ForbiddenException('この投稿を削除する権限がありません');
        }
    
        // POSTメソッドのみ許可
        $this->request->allowMethod(['post', 'delete']);
    
        if ($this->Portfolios->delete($portfolio)) {
            $this->Flash->success('投稿を削除しました');
        } else {
            $this->Flash->error('投稿の削除に失敗しました');
        }
    
        return $this->redirect(['controller' => 'Users', 'action' => 'profile']);
    }
    

    /**
     * 公開・非公開アクション
     */
    public function togglePublic($id)
    {
        $portfolio = $this->Portfolios->get($id);
        if ($portfolio->user_id !== $this->request->getAttribute('identity')->get('id')) {
            throw new ForbiddenException();
        }

        $portfolio->is_public = !$portfolio->is_public;
        $this->Portfolios->save($portfolio);

        return $this->redirect(['controller' => 'Users', 'action' => 'profile']);
    }

}
