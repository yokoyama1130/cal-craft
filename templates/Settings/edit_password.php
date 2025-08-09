<!-- templates/Settings/edit_password.php -->
<h2>パスワードの変更</h2>
<?= $this->Form->create(null, ['url' => ['action' => 'updatePassword']]) ?>
  <?= $this->Form->control('current_password', [
      'type' => 'password', 'label' => '現在のパスワード', 'required' => true
  ]) ?>
  <?= $this->Form->control('new_password', [
      'type' => 'password', 'label' => '新しいパスワード', 'required' => true
  ]) ?>
  <?= $this->Form->control('new_password_confirm', [
      'type' => 'password', 'label' => '新しいパスワード（確認）', 'required' => true
  ]) ?>
  <div style="display:flex;gap:8px">
    <?= $this->Form->button('変更する', ['class' => 'btn btn-primary']) ?>
    <?= $this->Html->link('戻る', ['action' => 'index'], ['class' => 'btn']) ?>
  </div>
<?= $this->Form->end() ?>
