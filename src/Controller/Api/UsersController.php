<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\Utility\Text;

class UsersController extends AppController
{
    /**
     * initialize
     *
     * コントローラ初期化処理。
     * - Users テーブルをロードし、データベース操作を可能にする。
     * - ビュービルダーを JSON 出力専用に設定し、APIレスポンスをJSON形式で統一する。
     * - Authentication コンポーネントなどは AppController 側で共通的にロードされる前提。
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        // UsersテーブルだけでOK（コンポーネントはAppControllerから継承される想定）
        $this->Users = $this->fetchTable('Users');

        // JSON固定
        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * beforeFilter
     *
     * アクション実行前の共通フィルタ処理。
     * - Authentication コンポーネントがロードされている場合、
     *   未ログイン状態でも `register` アクションを許可する。
     * - これにより、新規登録APIが認証不要で利用可能になる。
     *
     * @param \Cake\Event\EventInterface $event イベントオブジェクト
     * @return void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        if ($this->components()->has('Authentication')) {
            $this->Authentication->allowUnauthenticated(['register']);
        }
    }

    /**
     * register
     *
     * 新規ユーザー登録API。
     * - JSONまたはフォームデータで送信された `name`・`email`・`password` を受け取り、新規ユーザーを作成する。
     * - `sns_links` を空のJSONで初期化し、`email_verified` を false に設定。
     * - 登録成功時には確認メールを送信し、メール内のURLから認証処理を行う。
     * - 送信に失敗した場合はログを記録しつつ、登録自体は完了させる。
     * - バリデーションエラー時は HTTP 422 でエラー内容をJSONで返す。
     *
     * @return void
     */
    public function register()
    {
        $this->request->allowMethod(['post']);

        $user = $this->Users->newEmptyEntity();
        $data = json_decode((string)$this->request->getBody(), true) ?? $this->request->getData();

        $data['sns_links'] = json_encode([
            'twitter' => '',
            'github' => '',
            'youtube' => '',
            'instagram' => '',
        ]);

        $user = $this->Users->patchEntity($user, $data);
        $user->email_verified = false;
        $user->email_token = Text::uuid();

        if ($this->Users->save($user)) {
            try {
                $mailer = new \Cake\Mailer\Mailer('default');
                $mailer->setTo($user->email)
                    ->setSubject('【OrcaFolio】メール認証のお願い')
                    ->deliver(
                        "以下のURLをクリックしてメール認証を完了してください：\n\n" .
                        \Cake\Routing\Router::url(
                            ['controller' => 'Users', 'action' => 'verifyEmail', $user->email_token, 'prefix' => false],
                            true
                        )
                    );
            } catch (\Throwable $e) {
                \Cake\Log\Log::warning('Mail send failed: ' . $e->getMessage());
            }

            $this->set([
                'success' => true,
                'message' => '確認メールを送信しました。メールをご確認ください。',
                'user_id' => $user->id,
                '_serialize' => ['success', 'message', 'user_id'],
            ]);

            return;
        }

        $this->response = $this->response->withStatus(422);
        $this->set([
            'success' => false,
            'errors' => $user->getErrors(),
            '_serialize' => ['success', 'errors'],
        ]);
    }
}
