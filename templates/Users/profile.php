<div class="container mt-5">

<h2><?= h($user->name) ?>さんのプロフィール</h2>

<div>
    <?= $this->Html->link("フォロー {$followingCount}人", ['action' => 'followings', $user->id]) ?>
    /
    <?= $this->Html->link("フォロワー {$followerCount}人", ['action' => 'followers', $user->id]) ?>
</div>

<?php if ($this->request->getAttribute('identity')->get('id') !== $user->id): ?>
    <?php if ($isFollowing): ?>
        <?= $this->Form->postLink('フォロー解除', ['controller' => 'Follows', 'action' => 'unfollow', $user->id], ['class' => 'btn btn-outline-secondary']) ?>
    <?php else: ?>
        <?= $this->Form->postLink('フォロー', ['controller' => 'Follows', 'action' => 'follow', $user->id], ['class' => 'btn btn-primary']) ?>
    <?php endif; ?>
<?php endif; ?>

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
