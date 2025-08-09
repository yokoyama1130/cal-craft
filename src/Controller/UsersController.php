<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Utility\Text;
use Cake\Mailer\Mailer;
use Cake\Routing\Router;

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
            $data = $this->request->getData();
    
            // SNSリンクを空のJSONとして初期化（必要に応じて個別に設定可）
            $data['sns_links'] = json_encode([
                'twitter' => '',
                'github' => '',
                'youtube' => '',
                'instagram' => ''
            ]);

            $user = $this->Users->patchEntity($user, $data);
    
            $user->email_verified = false;
            $user->email_token = Text::uuid(); // ランダムトークン

            if ($this->Users->save($user)) {
                $mailer = new Mailer('default');
                // $mailer->setTo('nunouvlog@gmail.com')
                //     ->setSubject('テスト送信')
                //     ->deliver('CakePHPから送ったテストメールです。');

                // 認証メール送信
                $mailer = new Mailer('default');
                $mailer->setTo($user->email)
                    ->setSubject('【Calcraft】メール認証のお願い')
                    ->deliver("以下のURLをクリックしてメール認証を完了してください：\n\n" .
                        Router::url(['controller' => 'Users', 'action' => 'verifyEmail', $user->email_token], true));
    
                $this->Flash->success('確認メールを送信しました。メールをご確認ください。');
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
                return $this->redirect(['action' => 'profile', $user->id]);
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

    public function verifyEmail($token = null)
    {
        $user = $this->Users->find()->where(['email_token' => $token])->first();

        if (!$user) {
            $this->Flash->error('無効な認証リンクです。');
            return $this->redirect(['action' => 'login']);
        }

        $user->email_verified = true;
        $user->email_token = null;

        if ($this->Users->save($user)) {
            $this->Flash->success('メールアドレスの認証が完了しました。ログインできます。');
            return $this->redirect(['action' => 'login']);
        } else {
            $this->Flash->error('認証処理中にエラーが発生しました。');
        }
    }

    public function resendVerification()
    {
        if ($this->request->is('post')) {
            $email = $this->request->getData('email');
            $user = $this->Users->find()->where(['email' => $email])->first();

            if (!$user) {
                $this->Flash->error('該当するメールアドレスは登録されていません。');
                return $this->redirect(['action' => 'resendVerification']);
            }
            if ($user->email_verified) {
                $this->Flash->success('すでに認証済みです。ログインしてください。');
                return $this->redirect(['action' => 'login']);
            }

            $user->email_token = \Cake\Utility\Text::uuid();
            if ($this->Users->save($user)) {
                $mailer = new \Cake\Mailer\Mailer('default');
                $mailer->setTo($user->email)
                    ->setSubject('【Calcraft】メール認証の再送')
                    ->deliver("以下のURLから認証を完了してください：\n\n" .
                        \Cake\Routing\Router::url(['controller' => 'Users', 'action' => 'verifyEmail', $user->email_token], true));

                $this->Flash->success('認証メールを再送しました。メールをご確認ください。');
                return $this->redirect(['action' => 'login']);
            }

            $this->Flash->error('再送に失敗しました。時間をおいてお試しください。');
        }
    }

}
