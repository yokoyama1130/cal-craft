<?php
declare(strict_types=1);

namespace App\Controller;

class UsersController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authentication.Authentication');
        $this->loadModel('Follows');
        $this->loadModel('Portfolios');
    }

    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['login', 'register']);
    }

    public function login()
    {
        $this->request->allowMethod(['get', 'post']);
        $result = $this->Authentication->getResult();

        if ($result->isValid()) {
            return $this->redirect(['controller' => 'Top', 'action' => 'index']);
        }

        if ($this->request->is('post')) {
            $this->Flash->error('ログインに失敗しました');
        }
    }

    public function logout()
    {
        $result = $this->Authentication->getResult();
        if ($result->isValid()) {
            $this->Authentication->logout();
            $this->Flash->success('ログアウトしました');
        }
        return $this->redirect('/');
    }

    public function register()
    {
        $user = $this->Users->newEmptyEntity();
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success('登録完了しました。ログインしてください。');
                return $this->redirect(['action' => 'login']);
            }
            $this->Flash->error('登録に失敗しました。');
        }
        $this->set(compact('user'));
    }

    public function profile($id = null)
    {
        $userId = $id ?? $this->request->getAttribute('identity')->get('id');
        $authId = $this->request->getAttribute('identity')->get('id');

        $user = $this->Users->get($userId);

        $followerCount = $this->Follows->find()
            ->where(['followed_id' => $userId])
            ->count();

        $followingCount = $this->Follows->find()
            ->where(['follower_id' => $userId])
            ->count();

        $isFollowing = false;
        if ($authId && $authId != $userId) {
            $isFollowing = $this->Follows->exists([
                'follower_id' => $authId,
                'followed_id' => $userId
            ]);
        }

        $portfolios = $this->Portfolios->find()
            ->where(['user_id' => $userId])
            ->order(['created' => 'DESC'])
            ->toArray();

        $this->set(compact('user', 'followerCount', 'followingCount', 'isFollowing', 'portfolios'));
    }

    public function view($id)
    {
        $authId = $this->request->getAttribute('identity')->get('id');

        $user = $this->Users->get($id);
        $followerCount = $this->Follows->find()
            ->where(['followed_id' => $id])
            ->count();

        $followingCount = $this->Follows->find()
            ->where(['follower_id' => $id])
            ->count();

        $isFollowing = false;
        if ($authId && $authId != $id) {
            $isFollowing = $this->Follows->exists([
                'follower_id' => $authId,
                'followed_id' => $id
            ]);
        }

        $portfolios = $this->Portfolios->find()
            ->where(['user_id' => $id])
            ->order(['created' => 'DESC'])
            ->toArray();

        $this->set(compact('user', 'followerCount', 'followingCount', 'isFollowing', 'portfolios'));
    }

    public function index()
    {
        $users = $this->paginate($this->Users);
        $this->set(compact('users'));
    }

    public function add()
    {
        $user = $this->Users->newEmptyEntity();
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $this->set(compact('user'));
    }

    public function edit()
    {
        // ログイン中のユーザーIDを取得
        $userId = $this->request->getAttribute('identity')->get('id');
        $user = $this->Users->get($userId);
    
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            if (!empty($data['icon']) && $data['icon']->getError() === UPLOAD_ERR_OK) {
                $filename = time() . '_' . $data['icon']->getClientFilename();
                $targetPath = WWW_ROOT . 'img' . DS . 'icons' . DS . $filename;
            
                // ディレクトリがなければ作成
                if (!file_exists(WWW_ROOT . 'img' . DS . 'icons')) {
                    mkdir(WWW_ROOT . 'img' . DS . 'icons', 0775, true);
                }
            
                $data['icon']->moveTo($targetPath);
            
                // DBに保存するパス
                $data['icon_path'] = 'icons/' . $filename;
            }
    
            // SNSリンクをJSONに変換
            $sns = [
                'twitter' => $data['twitter'] ?? '',
                'github' => $data['github'] ?? '',
                'youtube' => $data['youtube'] ?? '',
                'instagram' => $data['instagram'] ?? ''
            ];
            $data['sns_links'] = json_encode($sns);
    
            $user = $this->Users->patchEntity($user, $data);
            if ($this->Users->save($user)) {
                $this->Flash->success(__('プロフィールを更新しました。'));
                return $this->redirect(['action' => 'view', $user->id]);
            }
            $this->Flash->error(__('更新に失敗しました。'));
        } else {
            // JSONを配列に変換してViewへ渡す
            $snsLinks = json_decode($user->sns_links, true) ?? [];
            $user->twitter = $snsLinks['twitter'] ?? '';
            $user->github = $snsLinks['github'] ?? '';
            $user->youtube = $snsLinks['youtube'] ?? '';
            $user->instagram = $snsLinks['instagram'] ?? '';
        }

        $this->set(compact('user'));
    }

    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function followings($id)
    {
        $this->loadModel('Follows');
        $this->loadModel('Users');

        $followings = $this->Follows->find()
            ->where(['follower_id' => $id])
            ->contain(['FollowedUsers']) // alias 定義済みである必要あり
            ->all();

        $this->set(compact('followings'));
    }

    public function followers($id)
    {
        $this->loadModel('Follows');
        $this->loadModel('Users');

        $followers = $this->Follows->find()
            ->where(['followed_id' => $id])
            ->contain(['Users']) // 'follower' 側の Users
            ->all();

        $this->set(compact('followers'));
    }

    public function search()
    {
        $this->request->allowMethod(['get']);
        $keyword = $this->request->getQuery('q');

        $users = [];
        if (!empty($keyword)) {
            $users = $this->Users->find()
                ->where(['Users.name LIKE' => '%' . $keyword . '%'])
                ->limit(50)
                ->toArray();
        }

        $this->set(compact('users', 'keyword'));
    }

}
