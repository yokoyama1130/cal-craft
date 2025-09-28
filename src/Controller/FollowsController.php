<?php
declare(strict_types=1);

namespace App\Controller;

class FollowsController extends AppController
{
    /**
     * イニシャライズ
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authentication.Authentication');
        $this->Follows = $this->fetchTable('Follows');
        $this->Notifications = $this->fetchTable('Notifications');
    }

    /**
     * フォローアクション
     *
     * 指定されたユーザーをログイン中のユーザーがフォローします。
     *
     * 主な処理内容:
     * - 自分自身をフォローしようとした場合はリダイレクトして終了
     * - すでにフォロー済みかを Follows テーブルで確認
     * - 未フォローなら Follows テーブルに新規レコードを作成
     * - さらに Notifications テーブルにフォロー通知を作成
     * - 処理完了後は直前のページへリダイレクト
     *
     * @param int $userId フォロー対象ユーザーID
     * @return \Cake\Http\Response リダイレクトレスポンス
     */
    public function follow($userId)
    {
        $followerId = $this->request->getAttribute('identity')->get('id');

        if ($followerId == $userId) {
            return $this->redirect($this->referer());
        }

        // すでにフォローしているか確認
        $exists = $this->Follows->exists([
            'follower_id' => $followerId,
            'followed_id' => $userId,
        ]);

        if (!$exists) {
            // フォローデータ保存
            $follow = $this->Follows->newEntity([
                'follower_id' => $followerId,
                'followed_id' => $userId,
            ]);
            $this->Follows->save($follow);

            // ✅ 通知データ作成
            $notification = $this->Notifications->newEntity([
                'user_id' => $userId, // 通知の受け取り手（フォローされた人）
                'sender_id' => $followerId, // フォローした人
                'type' => 'follow',
                'is_read' => false,
            ]);
            $this->Notifications->save($notification);
        }

        return $this->redirect($this->referer());
    }

    /**
     * アンフォローアクション
     *
     * 指定されたユーザーをログイン中のユーザーがフォロー解除します。
     *
     * 主な処理内容:
     * - Follows テーブルから該当するフォローデータを検索
     * - 存在すれば削除（フォロー解除）
     * - 処理完了後は直前のページにリダイレクト
     *
     * @param int $userId フォロー解除対象のユーザーID
     * @return \Cake\Http\Response リダイレクトレスポンス
     */
    public function unfollow($userId)
    {
        $followerId = $this->request->getAttribute('identity')->get('id');

        $follow = $this->Follows->find()
            ->where([
                'follower_id' => $followerId,
                'followed_id' => $userId,
            ])
            ->first();

        if ($follow) {
            $this->Follows->delete($follow);
        }

        return $this->redirect($this->referer());
    }

    /**
     * Ajaxフォローアクション
     *
     * ログイン中のユーザーが指定されたユーザーをフォローします。
     * 通常の follow() とは異なり、非同期通信 (Ajax) 用に JSON レスポンスを返します。
     *
     * 主な処理内容:
     * - POST + Ajax リクエストのみ許可
     * - Follows テーブルに新しいフォロー関係を保存
     * - レスポンスとして "status" = "followed" を返す
     *
     * @param int|null $userId フォロー対象のユーザーID
     * @return void
     */
    public function followAjax($userId = null)
    {
        $this->request->allowMethod(['post', 'ajax']);
        $followerId = $this->request->getAttribute('identity')->get('id');

        $follow = $this->Follows->newEmptyEntity();
        $follow->follower_id = $followerId;
        $follow->followed_id = $userId;
        $this->Follows->save($follow);

        $this->set('status', 'followed');
        $this->viewBuilder()->setLayout('ajax');
        $this->render('/Element/json_response');
    }

    /**
     * Ajaxアンフォローアクション
     *
     * ログイン中のユーザーが指定されたユーザーのフォローを解除します。
     * 非同期通信 (Ajax) 用に設計されており、JSON レスポンスを返します。
     *
     * 主な処理内容:
     * - POST + Ajax リクエストのみ許可
     * - Follows テーブルから対象のフォロー関係を削除
     * - レスポンスとして "status" = "unfollowed" を返す
     *
     * @param int|null $userId アンフォロー対象のユーザーID
     * @return void
     */
    public function unfollowAjax($userId = null)
    {
        $this->request->allowMethod(['post', 'ajax']);
        $followerId = $this->request->getAttribute('identity')->get('id');

        $follow = $this->Follows->find()
            ->where(['follower_id' => $followerId, 'followed_id' => $userId])
            ->first();

        if ($follow) {
            $this->Follows->delete($follow);
        }

        $this->set('status', 'unfollowed');
        $this->viewBuilder()->setLayout('ajax');
        $this->render('/Element/json_response');
    }
}
