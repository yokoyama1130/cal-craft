<?php

// src/Mailer/UserMailer.php
declare(strict_types=1);

namespace App\Mailer;

use Cake\Mailer\Mailer;
use Cake\Routing\Router;

class UserMailer extends Mailer
{
    // 新メール宛に確認リンクを送る
    public function emailChangeConfirm($user, string $token): void
    {
        $confirmUrl = Router::url([
            'controller' => 'Settings',
            'action' => 'confirmEmail',
            $token
        ], true);

        $this
            ->setEmailFormat('text')
            ->setTo($user->new_email)
            ->setSubject('【CalCraft】メールアドレス確認のお願い')
            ->setViewVars(compact('user', 'confirmUrl'))
            ->viewBuilder()->setTemplate('email_change_confirm'); // templates/email/text/email_change_confirm.php
    }

    // 旧メールにも通知（任意）
    public function emailChangeNoticeOld($user): void
    {
        $this
            ->setEmailFormat('text')
            ->setTo($user->email)
            ->setSubject('【CalCraft】メール変更のリクエストがありました')
            ->setViewVars(compact('user'))
            ->viewBuilder()->setTemplate('email_change_notice_old'); // templates/email/text/email_change_notice_old.php
    }
}
