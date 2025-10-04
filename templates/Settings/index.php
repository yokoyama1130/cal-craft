<h2>設定</h2>
<?= $this->Flash->render() ?>

<div class="card">
  <h3>アカウント</h3>

  <div class="row">
    <div class="col">
      <strong>メールアドレス</strong><br>
      <?= h($user->email) ?>
    </div>
    <div class="col right">
      <?= $this->Html->link('変更', ['action' => 'editEmail'], ['class' => 'btn btn-primary']) ?>
    </div>
  </div>
  <hr>
  <div class="row">
    <div class="col">
      <strong>パスワード</strong><br>
      ********
      <?php if (!empty($user->modified)) : ?>
        <small class="text-muted">（最終更新: <?= h($user->modified->i18nFormat('yyyy-MM-dd HH:mm')) ?>）</small>
      <?php endif; ?>
    </div>
    <div class="col right">
      <?= $this->Html->link('変更', ['action' => 'editPassword'], ['class' => 'btn btn-primary']) ?>
    </div>
  </div>
  <hr>
  <div class="row">
    <div class="col">
      <strong>アカウント削除</strong><br>
      <small class="text-muted">この操作は元に戻せません</small>
    </div>
    <div class="col right">
      <?= $this->Html->link(
          'アカウントを削除',
          ['action' => 'deleteConfirm'],
          [
            'class' => 'btn btn-danger',
            'escapeTitle' => false,
            'confirm' => '本当にアカウントを削除しますか？この操作は取り消せません。',
          ]
      ) ?>
    </div>
  </div>
</div>

<style>
.card {
    padding:16px;
    border:1px solid #eee;
    border-radius:12px;
    max-width:720px
}
.row {
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin:12px 0
}
.col.right {
    text-align:right
}
.btn {
    padding:8px 12px;
    border-radius:8px;
    text-decoration:none;
    border:1px solid #ccc;
}
.btn.btn-primary {
    background:#0d6efd;
    color:#fff;
    border-color:#0d6efd;
}
.btn.btn-danger {
    background:#dc3545;
    color:#fff;
    border-color:#dc3545;
}
</style>
