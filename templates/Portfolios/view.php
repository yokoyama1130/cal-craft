<?php
/**
 * ポートフォリオ詳細＋コメント（かっこよく＆遊び心あり）
 */
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
<style>
.fade-in-up {
  animation: fadeInUp 1s ease;
}
.card-custom {
  border: none;
  border-radius: 16px;
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
  background: #fff;
  padding: 2rem;
  transition: transform 0.3s ease;
}
.card-custom:hover {
  transform: scale(1.02);
}
.card-custom h5 {
  font-weight: bold;
  color: #333;
  margin-top: 1.5rem;
}
.portfolio-section {
  margin-top: 2rem;
  border-left: 5px solid #0d6efd;
  padding-left: 1rem;
  background: #f9f9f9;
  padding: 1rem;
  border-radius: 8px;
}
.comment-box {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  padding: 1.5rem;
  margin-bottom: 2rem;
  transition: all 0.3s ease;
}
.comment-box:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}
.comment-author {
  font-weight: bold;
  color: #0d6efd;
}
.comment-time {
  font-size: 0.85rem;
  color: #888;
}
</style>

<div class="card card-custom fade-in-up">
  <p><strong>📄 詳細:</strong></p>
  <p><?= nl2br(h($portfolio->description)) ?></p>

  <p><strong>👤 投稿者:</strong>
    <?= $this->Html->link(h($portfolio->user->name), ['controller' => 'Users', 'action' => 'view', $portfolio->user->id], ['class' => 'text-muted small']) ?>
  </p>

  <?php if ($portfolio->thumbnail): ?>
    <p><strong>🖼️ サムネイル:</strong><br>
      <img src="<?= h($portfolio->thumbnail) ?>" alt="Thumbnail" class="img-fluid rounded" style="max-width: 400px;">
    </p>
  <?php endif; ?>

  <?php if ($portfolio->link): ?>
    <p><strong>🔗 関連リンク:</strong> <a href="<?= h($portfolio->link) ?>" target="_blank"><?= h($portfolio->link) ?></a></p>
  <?php endif; ?>

  <?php if (!empty($portfolio->category) && $portfolio->category->slug === 'mechanical'): ?>
    <div class="portfolio-section">
      <h4 class="mb-3">🔧 機械系ポートフォリオ詳細</h4>

      <?php if ($portfolio->purpose || $portfolio->basic_spec): ?>
        <h5>[1] 設計構想・目的</h5>
        <?php if ($portfolio->purpose): ?>
          <p><strong>目的／背景:</strong><br><?= nl2br(h($portfolio->purpose)) ?></p>
        <?php endif; ?>
        <?php if ($portfolio->basic_spec): ?>
          <p><strong>基本仕様:</strong><br><?= nl2br(h($portfolio->basic_spec)) ?></p>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ($portfolio->design_url || $portfolio->design_description || $portfolio->parts_list): ?>
        <h5>[2] 設計と部品情報</h5>
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

      <?php if ($portfolio->processing_method || $portfolio->processing_notes || $portfolio->analysis_method || $portfolio->analysis_result): ?>
        <h5>[3] 加工・解析情報</h5>
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

      <?php if ($portfolio->development_period || $portfolio->mechanical_notes || $portfolio->reference_links): ?>
        <h5>[4] 補足情報</h5>
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
    </div>
  <?php endif; ?>
</div>

<h3 class="mt-5">💬 コメント</h3>

<?php if (!empty($comments)): ?>
  <div class="mb-4">
    <?php foreach ($comments as $comment): ?>
      <div class="comment-box animate__animated animate__fadeInUp">
        <div class="comment-author">👤 <?= h($comment->user->name) ?></div>
        <div><?= nl2br(h($comment->content)) ?></div>
        <div class="comment-time">🕒 <?= $comment->created->nice() ?></div>

        <?php if ($comment->user_id === $this->request->getAttribute('identity')->get('id')): ?>
          <div class="mt-2">
            <?= $this->Html->link('✏️ 編集', ['controller' => 'Comments', 'action' => 'edit', $comment->id], ['class' => 'btn btn-sm btn-outline-secondary me-2']) ?>
            <?= $this->Form->postLink('🗑️ 削除', ['controller' => 'Comments', 'action' => 'delete', $comment->id], [
              'confirm' => '本当に削除しますか？',
              'class' => 'btn btn-sm btn-outline-danger'
            ]) ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <p>まだコメントはありません。最初のコメントをどうぞ！</p>
<?php endif; ?>

<?= $this->Form->create(null, ['url' => ['controller' => 'Comments', 'action' => 'add']]) ?>
<?= $this->Form->hidden('portfolio_id', ['value' => $portfolio->id]) ?>
<div class="form-floating">
  <?= $this->Form->control('content', [
    'label' => 'コメントを書く',
    'rows' => 3,
    'class' => 'form-control',
    'placeholder' => 'コメントを書く',
    'style' => 'height: 100px'
  ]) ?>
</div>
<?= $this->Form->button('🚀 投稿する', ['class' => 'btn btn-primary mt-2']) ?>
<?= $this->Form->end() ?>