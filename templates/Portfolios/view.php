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

