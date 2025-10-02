<?php
/**
 * templates/Employer/Auth/login.php
 * 企業ログイン
 *
 * AuthenticationService (prefix=Employer) は
 * fields => ['username' => 'auth_email', 'password' => 'auth_password']
 * を期待している前提。
 */
?>
<div class="container py-5" style="max-width:520px;">
  <h2 class="mb-4 fw-semibold text-center">会社アカウントログイン</h2>

    <?= $this->Form->create(null, [
        // ここを controller 付きにしない
        'url' => '/employer/login',
        'autocomplete' => 'off',
    ]) ?>

  <?= $this->Form->control('auth_email', [
    'label' => 'Email',
    'type' => 'email',
    'required' => true,
    'class' => 'form-control',
  ]) ?>

  <?= $this->Form->control('auth_password', [
    'label' => 'Password',
    'type' => 'password',
    'required' => true,
    'class' => 'form-control',
  ]) ?>

  <div class="mt-3">
    <?= $this->Form->button('Sign in', ['class' => 'btn btn-primary w-100']) ?>
  </div>

  <?= $this->Form->end() ?>
  <p class="mt-3">
    会員登録がまだの方は
    <?= $this->Html->link('こちら', [
        'prefix' => false,
        'controller' => 'Companies',
        'action' => 'add',
    ]) ?>
    から新規登録をお願いいたします。
  </p>
</div>
