<!-- templates/Settings/index.php -->
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
      <?= $this->Html->link('変更', ['action' => 'editEmail'], ['class' => 'btn btn-secondary']) ?>
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
      <?= $this->Html->link('変更', ['action' => 'editPassword'], ['class' => 'btn btn-secondary']) ?>
    </div>
  </div>
</div>

<style>
.card{padding:16px;border:1px solid #eee;border-radius:12px;max-width:720px}
.row{display:flex;align-items:center;justify-content:space-between;margin:12px 0}
.col.right{text-align:right}
.btn{padding:8px 12px;border-radius:8px;text-decoration:none;border:1px solid #ccc}
.btn.btn-secondary{background:#f5f5f5}
</style>
