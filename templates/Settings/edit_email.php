<!-- templates/Settings/edit_email.php -->
<h2>メールアドレスの変更</h2>
<p>現在: <strong><?= h($user->email) ?></strong></p>

<?= $this->Form->create(null, ['url' => ['action' => 'updateEmail']]) ?>
  <?= $this->Form->control('current_password', [
      'type' => 'password', 'label' => '現在のパスワード', 'required' => true
  ]) ?>
  <?= $this->Form->control('new_email', [
      'type' => 'email', 'label' => '新しいメールアドレス', 'required' => true
  ]) ?>
  <div style="display:flex;gap:8px">
    <?= $this->Form->button('確認メールを送る', ['class' => 'btn btn-primary']) ?>
    <?= $this->Html->link('戻る', ['action' => 'index'], ['class' => 'btn']) ?>
  </div>
<?= $this->Form->end() ?>
