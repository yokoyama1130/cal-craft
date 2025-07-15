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
        <?= $this->Form->postLink(
            '削除',
            ['controller' => 'Portfolios', 'action' => 'delete', $p->id],
            [
                'class' => 'btn btn-outline-danger btn-sm',
                'confirm' => '本当にこの投稿を削除しますか？'
            ]
        ) ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
