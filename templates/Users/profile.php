<div class="container mt-5">
  <h2 class="mb-4">あなたの投稿一覧</h2>

  <?php foreach ($portfolios as $p): ?>
    <div class="card mb-3">
      <div class="card-body">
        <h5><?= h($p->title) ?></h5>
        <p><?= h($p->description) ?></p>
        <p class="text-muted">公開状態：<?= $p->is_public ? '公開' : '非公開' ?></p>

        <div class="d-flex">
          <a href="/portfolios/edit/<?= $p->id ?>" class="btn btn-outline-primary btn-sm me-2">編集</a>
          <form action="/portfolios/delete/<?= $p->id ?>" method="post" style="display:inline;">
            <button class="btn btn-outline-danger btn-sm" onclick="return confirm('本当に削除しますか？')">削除</button>
          </form>
          <form action="/portfolios/toggle-public/<?= $p->id ?>" method="post" class="ms-2">
            <button class="btn btn-outline-secondary btn-sm">
              <?= $p->is_public ? '非公開にする' : '公開にする' ?>
            </button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
