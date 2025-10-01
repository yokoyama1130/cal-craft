<?php
declare(strict_types=1);

namespace App\Mailer;

use Cake\Mailer\Mailer;
use Cake\Routing\Router;

class UserMailer extends Mailer
{
    /**
     * 初期化処理
     *
     * - メール送信プロファイルを "default" に設定
     * - From アドレスが未設定の場合は no-reply@your-domain.tld を既定値として利用
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        // app_local.php の 'Email' => ['default' => ...] を使う
        $this->setProfile('default');

        // 念のため既定Fromも明示（プロバイダで認証済みドメインにする）
        if (!$this->getFrom()) {
            $this->setFrom('no-reply@your-domain.tld', 'OrcaFolio');
        }
    }

    /**
     * メールアドレス変更確認メール送信
     *
     * 新しいメールアドレス宛に確認リンクを送信する。
     * ユーザーがリンクを踏むことで変更が確定する。
     *
     * @param \App\Model\Entity\User $user  対象ユーザーエンティティ
     * @param string $token                  確認用トークン（URLに付与）
     * @return void
     * @throws \Throwable 送信エラーが発生した場合に再スローされる
     */
    public function emailChangeConfirm($user, string $token): void
    {
        $confirmUrl = Router::url([
            'controller' => 'Settings',
            'action' => 'confirmEmail',
            $token,
        ], true); // full base URL 必須（app.phpのApp.fullBaseUrlも設定推奨）

        $this
            ->setEmailFormat('text')
            ->setTo($user->new_email)
            ->setSubject('【OrcaFolio】メールアドレス確認のお願い')
            ->setViewVars(compact('user', 'confirmUrl'))
            ->viewBuilder()->setTemplate('email_change_confirm');

        // 例外を拾ってログへ
        try {
            $this->deliver();
        } catch (\Throwable $e) {
            \Cake\Log\Log::error('emailChangeConfirm send failed: ' . $e->getMessage());
            throw $e; // 画面で気づけるように再スロー（開発中はこれでOK）
        }
    }

    /**
     * 旧メールアドレス宛の通知メール送信
     *
     * ユーザーがメール変更をリクエストした際に、旧アドレスに通知を送信する。
     * 送信失敗は致命的でないため、例外は握りつぶす設計。
     *
     * @param \App\Model\Entity\User $user  対象ユーザーエンティティ
     * @return void
     */
    public function emailChangeNoticeOld($user): void
    {
        $this
            ->setEmailFormat('text')
            ->setTo($user->email)
            ->setSubject('【OrcaFolio】メール変更のリクエストがありました')
            ->setViewVars(compact('user'))
            ->viewBuilder()->setTemplate('email_change_notice_old');

        try {
            $this->deliver();
        } catch (\Throwable $e) {
            \Cake\Log\Log::warning('emailChangeNoticeOld send failed: ' . $e->getMessage());
            // 通知なので失敗は致命的でない。握りつぶすなら再スローしない選択もあり。
        }
    }
}
