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
  <h1 class="h4 mb-3">Employer Login</h1>

    <?= $this->Form->create(null, [
        // ここを controller 付きにしない
        'url' => '/employer/login',
        'autocomplete' => 'off'
    ]) ?>

  <?= $this->Form->control('auth_email', [
    'label' => 'Email',
    'type' => 'email',
    'required' => true,
    'class' => 'form-control'
  ]) ?>

  <?= $this->Form->control('auth_password', [
    'label' => 'Password',
    'type' => 'password',
    'required' => true,
    'class' => 'form-control'
  ]) ?>

  <div class="mt-3">
    <?= $this->Form->button('Sign in', ['class' => 'btn btn-primary w-100']) ?>
  </div>

  <?= $this->Form->end() ?>
</div>
