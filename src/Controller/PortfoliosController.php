<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\ORM\TableRegistry;

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
    // src/Controller/PortfoliosController.php

    public function index()
    {
        $this->loadModel('Likes');
        $this->loadModel('Portfolios');
    
        $identity = $this->request->getAttribute('identity');
        $userId = $identity ? $identity->get('id') : null;
    
        $portfolios = $this->Portfolios->find()
            ->where(['is_public' => true])
            ->order(['created' => 'DESC'])
            ->limit(10)
            ->toArray();
    
        foreach ($portfolios as $p) {
            $p->like_count = $this->Likes->find()
                ->where(['portfolio_id' => $p->id])
                ->count();
    
            // ✅ 自分がいいねしてるかチェック
            $p->liked_by_me = false;
            if ($userId !== null) {
                $p->liked_by_me = $this->Likes->exists([
                    'user_id' => $userId,
                    'portfolio_id' => $p->id
                ]);
            }
        }
    
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

        // 非公開の投稿は本人以外見れない
        if (!$portfolio->is_public && $portfolio->user_id !== $this->request->getAttribute('identity')->get('id')) {
            $this->Flash->error('この投稿にはアクセスできません。');
            return $this->redirect(['action' => 'index']);
        }

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
    public function edit($id = null)
    {
        $portfolio = $this->Portfolios->get($id);
    
        // ログインユーザー以外が編集しようとしたらリダイレクト
        if ($portfolio->user_id !== $this->request->getAttribute('identity')->getIdentifier()) {
            $this->Flash->error('この投稿を編集する権限がありません。');
            return $this->redirect(['controller' => 'Users', 'action' => 'profile']);
        }
    
        if ($this->request->is(['patch', 'post', 'put'])) {
            $portfolio = $this->Portfolios->patchEntity($portfolio, $this->request->getData());
            if ($this->Portfolios->save($portfolio)) {
                $this->Flash->success(__('投稿が更新されました。'));
                return $this->redirect(['controller' => 'Users', 'action' => 'profile']);
            }
            $this->Flash->error(__('投稿の更新に失敗しました。'));
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
