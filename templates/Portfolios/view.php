<div class="card p-4 shadow-sm">
  <p><strong>詳細:</strong></p>
  <p><?= nl2br(h($portfolio->description)) ?></p>

  <p><strong>投稿者:</strong> 
    <?= $this->Html->link(
        h($portfolio->user->name),
        ['controller' => 'Users', 'action' => 'view', $portfolio->user->id],
        ['class' => 'text-muted small']
    ) ?>
  </p>

  <p><strong>サムネイル:</strong><br>
    <img src="<?= h($portfolio->thumbnail) ?>" alt="Thumbnail" class="img-fluid rounded" style="max-width: 400px;">
  </p>
  
  <?php if ($portfolio->link): ?>
    <p><strong>関連リンク:</strong> <a href="<?= h($portfolio->link) ?>" target="_blank"><?= h($portfolio->link) ?></a></p>
  <?php endif; ?>
</div>

<h3 class="mt-5">コメント</h3>

<?php if (!empty($comments)): ?>
    <ul class="list-group mb-4">
        <?php foreach ($comments as $comment): ?>
            <li class="list-group-item">
                <strong><?= h($comment->user->name) ?></strong><br>
                <?= nl2br(h($comment->content)) ?><br>
                <small class="text-muted"><?= $comment->created->nice() ?></small>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>コメントはまだありません。</p>
<?php endif; ?>

<?= $this->Form->create(null, ['url' => ['controller' => 'Comments', 'action' => 'add']]) ?>
<?= $this->Form->hidden('portfolio_id', ['value' => $portfolio->id]) ?>
<?= $this->Form->control('content', ['label' => false, 'rows' => 3, 'placeholder' => 'コメントを書く']) ?>
<?= $this->Form->button('投稿', ['class' => 'btn btn-primary mt-2']) ?>
<?= $this->Form->end() ?>
