<!-- templates/Settings/delete_confirm.php -->
<h2>アカウント削除</h2>
<?= $this->Flash->render() ?>

<div class="danger-card">
  <p><strong>注意：</strong> アカウントを削除するとログインできなくなります。投稿・データは規約に基づき保持/匿名化される場合があります。</p>
  <ul>
    <li>メールアドレス等の個人情報は匿名化されます</li>
    <li>再開希望の際はサポートにお問い合わせください</li>
  </ul>
</div>

<?= $this->Form->create(null, ['url' => ['action' => 'deleteAccount']]) ?>
  <?= $this->Form->control('current_password', [
        'type' => 'password', 'label' => '現在のパスワード', 'required' => true,
  ]) ?>
  <?= $this->Form->control('confirm_keyword', [
        'type' => 'text',
        'label' => '確認のため「DELETE」と入力',
        'placeholder' => 'DELETE', 'required' => true,
  ]) ?>
  <div class="actions">
    <?= $this->Form->button('アカウントを削除する', ['class' => 'btn btn-danger']) ?>
    <?= $this->Html->link('キャンセル', ['action' => 'index'], ['class' => 'btn']) ?>
  </div>
<?= $this->Form->end() ?>

<style>
.danger-card{background:#fff5f5;border:1px solid #ffd6d6;padding:16px;border-radius:12px;max-width:720px;margin-bottom:16px}
.actions{display:flex;gap:8px}
.btn{padding:8px 12px;border-radius:8px;border:1px solid #ccc;text-decoration:none}
.btn-danger{background:#dc3545;color:#fff;border-color:#dc3545}
</style>
