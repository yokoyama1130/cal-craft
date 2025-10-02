<?php
// templates/Employer/Auth/resend_verification.php
/**
 * 企業メール認証の再送フォーム
 */
?>
<div class="container py-5" style="max-width:520px;">
  <h2 class="mb-4 fw-semibold text-center">認証メールの再送</h2>

  <p class="text-muted">
    企業ログインに使うメールアドレスを入力して、認証メールを再送してください。
  </p>

  <?= $this->Form->create(null, [
        'url' => ['prefix' => 'Employer', 'controller' => 'Auth', 'action' => 'resendVerification'],
        'autocomplete' => 'off',
  ]) ?>

  <?php
    $prefill = $this->getRequest()->getQuery('auth_email') ?? '';
    echo $this->Form->control('email', [
      'label' => '登録メールアドレス',
      'type' => 'email',
      'required' => true,
      'value' => $prefill,
      'class' => 'form-control',
    ]);
    ?>

  <div class="mt-3">
    <?= $this->Form->button('認証メールを再送する', ['class' => 'btn btn-primary w-100']) ?>
  </div>

  <?= $this->Form->end() ?>

  <p class="mt-3 text-center">
    <?= $this->Html->link('← ログインに戻る', ['prefix' => 'Employer', 'controller' => 'Auth', 'action' => 'login']) ?>
  </p>
</div>
