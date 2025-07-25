<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Collection\Collection;

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
            ->contain(['Users'])
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
        $this->loadModel('Follows');
        $this->loadModel('Comments');
    
        // ポートフォリオ取得（投稿者情報、カテゴリ、コメント含む）
        $portfolio = $this->Portfolios->get($id, [
            'contain' => ['Users', 'Categories', 'Comments' => ['Users']],
        ]);
    
        // 非公開チェック（投稿者本人以外アクセス禁止）
        if (!$portfolio->is_public && $portfolio->user_id !== $this->request->getAttribute('identity')->get('id')) {
            $this->Flash->error('この投稿にはアクセスできません。');
            return $this->redirect(['action' => 'index']);
        }
    
        // コメント取得（並び順：新しい順）
        $comments = $this->Comments->find()
            ->where(['portfolio_id' => $id])
            ->contain(['Users'])
            ->order(['created' => 'DESC'])
            ->toArray();
    
        // 投稿者のユーザーID
        $userId = $portfolio->user_id;
    
        // ログイン中のユーザーID
        $authId = $this->request->getAttribute('identity')->get('id');
    
        // フォロー数／フォロワー数
        $followerCount = $this->Follows->find()
            ->where(['followed_id' => $userId])
            ->count();
    
        $followingCount = $this->Follows->find()
            ->where(['follower_id' => $userId])
            ->count();
    
        // ログイン中ユーザーがフォローしているか
        $isFollowing = false;
        if ($authId && $authId != $userId) {
            $isFollowing = $this->Follows->exists([
                'follower_id' => $authId,
                'followed_id' => $userId
            ]);
        }
    
        $this->set(compact('portfolio', 'comments', 'followerCount', 'followingCount', 'isFollowing'));
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
            $data = $this->request->getData();
    
            // サムネイル画像の処理
            $thumbnailFile = $this->request->getData('thumbnail_file');
            if ($thumbnailFile && $thumbnailFile->getError() === UPLOAD_ERR_OK) {
                $filename = Text::uuid() . '.' . pathinfo($thumbnailFile->getClientFilename(), PATHINFO_EXTENSION);
                $uploadPath = WWW_ROOT . 'uploads' . DS . $filename;
                $thumbnailFile->moveTo($uploadPath);
                $data['thumbnail'] = '/uploads/' . $filename;
            }
    
            $portfolio = $this->Portfolios->patchEntity($portfolio, $data);
            $portfolio->user_id = $this->request->getAttribute('identity')->get('id');
    
            if ($this->Portfolios->save($portfolio)) {
                $this->Flash->success('投稿が完了しました！');
                return $this->redirect(['controller' => 'Top', 'action' => 'index']);
            }
            $this->Flash->error('投稿に失敗しました。もう一度お試しください。');
        }
    
        // categories に slug も含めて渡す
        $categories = $this->Portfolios->Categories->find()
            ->select(['id', 'name', 'slug'])
            ->order(['id' => 'ASC'])
            ->all()
            ->map(function ($row) {
                return $row;
            })
            ->toArray();
    
        $this->set(compact('portfolio', 'categories'));
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
        // ✅ カテゴリ情報も一緒に取得！
        $portfolio = $this->Portfolios->get($id, [
            'contain' => ['Categories']
        ]);
    
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

    public function search()
    {
        $this->request->allowMethod(['get']);
        $keyword = $this->request->getQuery('q');

        $query = $this->Portfolios->find()
            ->contain(['Users']) // 投稿者名なども後で使いたければ
            ->where(['is_public' => true]);

        if (!empty($keyword)) {
            $query->andWhere([
                'OR' => [
                    'Portfolios.title LIKE' => '%' . $keyword . '%',
                    'Portfolios.description LIKE' => '%' . $keyword . '%',
                ]
            ]);
        }

        $portfolios = $query->order(['Portfolios.created' => 'DESC'])->toArray();

        $this->set(compact('portfolios', 'keyword'));
        $this->render('index'); // ← トップページ（index）テンプレートを再利用
    }


}
