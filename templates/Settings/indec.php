<!-- templates/Settings/index.php -->
<h2>設定</h2>

<?= $this->Flash->render() ?>

<section style="margin-bottom:2rem">
  <h3>メールアドレスの変更</h3>
  <p>確認メールのリンクを踏むまで変更は反映されません。</p>
  <?= $this->Form->create(null, ['url' => ['action' => 'updateEmail']]) ?>
    <?= $this->Form->control('current_password', [
      'type' => 'password',
      'label' => '現在のパスワード',
      'required' => true
    ]) ?>
    <?= $this->Form->control('new_email', [
      'type' => 'email',
      'label' => '新しいメールアドレス',
      'required' => true
    ]) ?>
    <?= $this->Form->button('確認メールを送る', ['class' => 'btn btn-primary']) ?>
  <?= $this->Form->end() ?>
</section>

<hr/>

<section>
  <h3>パスワードの変更</h3>
  <?= $this->Form->create(null, ['url' => ['action' => 'updatePassword']]) ?>
    <?= $this->Form->control('current_password', [
      'type' => 'password',
      'label' => '現在のパスワード',
      'required' => true
    ]) ?>
    <?= $this->Form->control('new_password', [
      'type' => 'password',
      'label' => '新しいパスワード',
      'required' => true
    ]) ?>
    <?= $this->Form->control('new_password_confirm', [
      'type' => 'password',
      'label' => '新しいパスワード（確認）',
      'required' => true
    ]) ?>
    <?= $this->Form->button('パスワードを変更', ['class' => 'btn btn-primary']) ?>
  <?= $this->Form->end() ?>
</section>
