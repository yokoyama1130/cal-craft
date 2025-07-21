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

  <!-- ▼ 機械系 専用セクション -->
  <?php if (!empty($portfolio->category) && $portfolio->category->slug === 'mechanical'): ?>
    <hr>
    <h4 class="mt-4 mb-3">🔧 機械系ポートフォリオ詳細</h4>

    <!-- [1] 設計構想・目的 -->
    <?php if ($portfolio->purpose || $portfolio->basic_spec): ?>
      <h5 class="mt-3">[1] 設計構想・目的</h5>
      <?php if ($portfolio->purpose): ?>
        <p><strong>目的／背景:</strong><br><?= nl2br(h($portfolio->purpose)) ?></p>
      <?php endif; ?>
      <?php if ($portfolio->basic_spec): ?>
        <p><strong>基本仕様:</strong><br><?= nl2br(h($portfolio->basic_spec)) ?></p>
      <?php endif; ?>
    <?php endif; ?>

    <!-- [2] 設計と部品情報 -->
    <?php if ($portfolio->design_url || $portfolio->design_description || $portfolio->parts_list): ?>
      <h5 class="mt-4">[2] 設計と部品情報</h5>
      <?php if ($portfolio->design_url): ?>
        <p><strong>設計書リンク:</strong> <a href="<?= h($portfolio->design_url) ?>" target="_blank"><?= h($portfolio->design_url) ?></a></p>
      <?php endif; ?>
      <?php if ($portfolio->design_description): ?>
        <p><strong>設計の説明:</strong><br><?= nl2br(h($portfolio->design_description)) ?></p>
      <?php endif; ?>
      <?php if ($portfolio->parts_list): ?>
        <p><strong>部品リスト:</strong><br><pre><?= h($portfolio->parts_list) ?></pre></p>
      <?php endif; ?>
    <?php endif; ?>

    <!-- [3] 加工・解析 -->
    <?php if (
      $portfolio->processing_method || $portfolio->processing_notes ||
      $portfolio->analysis_method || $portfolio->analysis_result
    ): ?>
      <h5 class="mt-4">[3] 加工・解析情報</h5>
      <?php if ($portfolio->processing_method): ?>
        <p><strong>加工方法:</strong><br><?= h($portfolio->processing_method) ?></p>
      <?php endif; ?>
      <?php if ($portfolio->processing_notes): ?>
        <p><strong>加工ノウハウ・注意点:</strong><br><?= nl2br(h($portfolio->processing_notes)) ?></p>
      <?php endif; ?>
      <?php if ($portfolio->analysis_method): ?>
        <p><strong>解析手法:</strong><br><?= h($portfolio->analysis_method) ?></p>
      <?php endif; ?>
      <?php if ($portfolio->analysis_result): ?>
        <p><strong>解析結果・考察:</strong><br><?= nl2br(h($portfolio->analysis_result)) ?></p>
      <?php endif; ?>
    <?php endif; ?>

    <!-- [4] 補足 -->
    <?php if (
      $portfolio->development_period || $portfolio->mechanical_notes || $portfolio->reference_links
    ): ?>
      <h5 class="mt-4">[4] 補足情報</h5>
      <?php if ($portfolio->development_period): ?>
        <p><strong>開発期間:</strong> <?= h($portfolio->development_period) ?></p>
      <?php endif; ?>
      <?php if ($portfolio->mechanical_notes): ?>
        <p><strong>工夫点・反省:</strong><br><?= nl2br(h($portfolio->mechanical_notes)) ?></p>
      <?php endif; ?>
      <?php if ($portfolio->reference_links): ?>
        <p><strong>参考資料・URL:</strong><br><pre><?= h($portfolio->reference_links) ?></pre></p>
      <?php endif; ?>
    <?php endif; ?>
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

                <?php if ($comment->user_id === $this->request->getAttribute('identity')->get('id')): ?>
                    <div class="mt-2">
                        <?= $this->Html->link('編集', ['controller' => 'Comments', 'action' => 'edit', $comment->id], ['class' => 'btn btn-sm btn-outline-secondary me-2']) ?>
                        <?= $this->Form->postLink('削除', ['controller' => 'Comments', 'action' => 'delete', $comment->id], [
                            'confirm' => '本当に削除しますか？',
                            'class' => 'btn btn-sm btn-outline-danger'
                        ]) ?>
                    </div>
                <?php endif; ?>
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