
// templates/email/text/email_change_confirm.php
<?= h($user->name ?? 'ユーザー') ?> 様

OrcaFront にてメールアドレス変更の確認です。
以下のURLを1時間以内に開いて変更を完了してください。

<?= $confirmUrl ?>


もしお心当たりが無い場合は、このメールは破棄してください。
