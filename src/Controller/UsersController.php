<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Mailer\Mailer;
use Cake\Routing\Router;
use Cake\Utility\Text;

class UsersController extends AppController
{
    /**
     * initialize
     *
     * コントローラ初期化処理。
     * Authentication コンポーネントをロードし、Follows と Portfolios テーブルを利用可能にする。
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authentication.Authentication');
        $this->Follows = $this->fetchTable('Follows');
        $this->Portfolios = $this->fetchTable('Portfolios');
    }

    /**
     * beforeFilter
     *
     * アクション実行前のフィルタ処理。
     * 未ログインでもアクセス可能なアクション（login/logout/register/verifyEmail/resendVerification）を許可する。
     *
     * @param \Cake\Event\EventInterface $event イベントオブジェクト
     * @return void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions([
            'login', 'logout', 'register', 'verifyEmail', 'resendVerification',
        ]);
    }

    /**
     * login
     *
     * ログインフォーム表示およびログイン処理を行う。
     * 認証成功時は /top にリダイレクト、失敗時はエラーメッセージを表示。
     *
     * @return \Cake\Http\Response|null
     */
    public function login()
    {
        $this->request->allowMethod(['get', 'post']);
        $result = $this->Authentication->getResult();

        if ($result->isValid()) {
            return $this->redirect('/top');
        }

        if ($this->request->is('post')) {
            $this->Flash->error('ログインに失敗しました');
        }
    }

    /**
     * logout
     *
     * 現在のユーザーをログアウトし、トップページへリダイレクトする。
     *
     * @return \Cake\Http\Response
     */
    public function logout()
    {
        $result = $this->Authentication->getResult();
        if ($result->isValid()) {
            $this->Authentication->logout();
            $this->Flash->success('ログアウトしました');
        }

        return $this->redirect('/');
    }

    /**
     * register
     *
     * 新規ユーザー登録処理。
     * 入力情報を保存し、確認メールを送信する。
     *
     * @return \Cake\Http\Response|null
     */
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
                'instagram' => '',
            ]);

            $user = $this->Users->patchEntity($user, $data);

            $user->email_verified = false;
            $user->email_token = Text::uuid(); // ランダムトークン

            if ($this->Users->save($user)) {
                $mailer = new Mailer('default');

                // ここはコメントアウトして大丈夫らしい（それでも認証メールが届く）
                // $mailer->setTo('nunouvlog@gmail.com')
                //     ->setSubject('テスト送信')
                //     ->deliver('CakePHPから送ったテストメールです。');

                // 認証メール送信
                $mailer = new Mailer('default');
                $mailer->setTo($user->email)
                    ->setSubject('【OrcaFolio】メール認証のお願い')
                    ->deliver("以下のURLをクリックしてメール認証を完了してください：\n\n" .
                        Router::url(['controller' => 'Users', 'action' => 'verifyEmail', $user->email_token], true));

                $this->Flash->success('確認メールを送信しました。メールをご確認ください。');

                return $this->redirect(['action' => 'login']);
            }

            $this->Flash->error('登録に失敗しました。');
        }
        $this->set(compact('user'));
    }

    /**
     * profile
     *
     * ログイン中または指定されたユーザーのプロフィールを表示する。
     * フォロー/フォロワー数、フォロー状態、投稿一覧を取得。
     *
     * @param int|null $id ユーザーID（未指定なら認証ユーザー）
     * @return void
     */
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
                'followed_id' => $userId,
            ]);
        }

        $portfolios = $this->Portfolios->find()
            ->where(['user_id' => $userId])
            ->order(['created' => 'DESC'])
            ->toArray();

        $this->set(compact('user', 'followerCount', 'followingCount', 'isFollowing', 'portfolios'));
    }

    /**
     * view
     *
     * 指定されたユーザーの公開プロフィールページを表示する。
     * フォロー/フォロワー数、フォロー状態、投稿一覧を取得。
     *
     * @param int $id ユーザーID
     * @return void
     */
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
                'followed_id' => $id,
            ]);
        }

        $portfolios = $this->Portfolios->find()
            ->where(['user_id' => $id])
            ->order(['created' => 'DESC'])
            ->toArray();

        $this->set(compact('user', 'followerCount', 'followingCount', 'isFollowing', 'portfolios'));
    }

    /**
     * index
     *
     * ユーザー一覧をページネーション付きで表示する。
     *
     * @return void
     */
    public function index()
    {
        $users = $this->paginate($this->Users);
        $this->set(compact('users'));
    }

    /**
     * add
     *
     * 管理用ユーザー追加処理。
     * フォーム入力を保存し、一覧へリダイレクト。
     *
     * @return \Cake\Http\Response|null
     */
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

    /**
     * edit
     *
     * ログイン中ユーザーのプロフィール編集処理。
     * アイコン画像アップロード、SNSリンク更新に対応。
     *
     * @return \Cake\Http\Response|null
     */
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
                'instagram' => $data['instagram'] ?? '',
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

    /**
     * delete
     *
     * 指定されたユーザーを削除する。
     * 削除成功/失敗のメッセージを表示し、一覧にリダイレクト。
     *
     * @param int|null $id ユーザーID
     * @return \Cake\Http\Response
     */
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

    /**
     * followings
     *
     * 指定ユーザーがフォローしているユーザー一覧を取得。
     *
     * @param int $id ユーザーID
     * @return void
     */
    public function followings($id)
    {
        $this->Follows = $this->fetchTable('Follows');
        $this->Users = $this->fetchTable('Users');

        $followings = $this->Follows->find()
            ->where(['follower_id' => $id])
            ->contain(['FollowedUsers']) // alias 定義済みである必要あり
            ->all();

        $this->set(compact('followings'));
    }

    /**
     * followers
     *
     * 指定ユーザーをフォローしているユーザー一覧を取得。
     *
     * @param int $id ユーザーID
     * @return void
     */
    public function followers($id)
    {
        $this->Follows = $this->fetchTable('Follows');
        $this->Users = $this->fetchTable('Users');

        $followers = $this->Follows->find()
            ->where(['followed_id' => $id])
            ->contain(['Users']) // 'follower' 側の Users
            ->all();

        $this->set(compact('followers'));
    }

    /**
     * search
     *
     * ユーザー名で検索し、最大50件の結果を返す。
     *
     * @return void
     */
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

    /**
     * verifyEmail
     *
     * メール認証リンクを処理し、ユーザーのメールを有効化する。
     *
     * @param string|null $token 認証トークン
     * @return \Cake\Http\Response|null
     */
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

    /**
     * resendVerification
     *
     * 未認証ユーザーにメール認証用リンクを再送信する。
     *
     * @return \Cake\Http\Response|null
     */
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
                    ->setSubject('【OrcaFolio】メール認証の再送')
                    ->deliver(
                        "以下のURLから認証を完了してください：\n\n" .
                        \Cake\Routing\Router::url(
                            [
                                'controller' => 'Users',
                                'action' => 'verifyEmail',
                                $user->email_token,
                            ],
                            true
                        )
                    );

                $this->Flash->success('認証メールを再送しました。メールをご確認ください。');

                return $this->redirect(['action' => 'login']);
            }

            $this->Flash->error('再送に失敗しました。時間をおいてお試しください。');
        }
    }
}
