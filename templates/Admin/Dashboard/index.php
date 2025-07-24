<h1 class="mb-4">管理ダッシュボード</h1>

<div class="row">
  <div class="col-md-6 mb-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">登録ユーザー数</h5>
        <p class="card-text display-6"><?= h($userCount) ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-6 mb-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">投稿数</h5>
        <p class="card-text display-6"><?= h($portfolioCount) ?></p>
      </div>
    </div>
  </div>
</div>
