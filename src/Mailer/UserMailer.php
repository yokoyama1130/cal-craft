<?php

declare(strict_types=1);

namespace App\Mailer;

use Cake\Mailer\Mailer;
use Cake\Routing\Router;

class UserMailer extends Mailer
{
    public function initialize(): void
    {
        parent::initialize();
        // app_local.php の 'Email' => ['default' => ...] を使う
        $this->setProfile('default');

        // 念のため既定Fromも明示（プロバイダで認証済みドメインにする）
        if (!$this->getFrom()) {
            $this->setFrom('no-reply@your-domain.tld', 'OrcaFront');
        }
    }

    public function emailChangeConfirm($user, string $token): void
    {
        $confirmUrl = Router::url([
            'controller' => 'Settings',
            'action' => 'confirmEmail',
            $token
        ], true); // full base URL 必須（app.phpのApp.fullBaseUrlも設定推奨）

        $this
            ->setEmailFormat('text')
            ->setTo($user->new_email)
            ->setSubject('【OrcaFront】メールアドレス確認のお願い')
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

    public function emailChangeNoticeOld($user): void
    {
        $this
            ->setEmailFormat('text')
            ->setTo($user->email)
            ->setSubject('【OrcaFront】メール変更のリクエストがありました')
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
