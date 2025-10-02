<!-- templates/Comments/edit.php -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="container py-4 fade-in">

  <!-- ページヘッダー -->
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2">
      <a href="<?= $this->Url->build(['controller' => 'Portfolios', 'action' => 'view', $comment->portfolio_id]) ?>"
         class="btn btn-light border">
        <i class="fa-solid fa-arrow-left"></i>
      </a>
      <div>
        <h2 class="mb-0 fw-semibold">コメントを編集</h2>
        <div class="text-muted small">
          <i class="fa-regular fa-file-lines me-1"></i>対象ポートフォリオ #<?= (int)$comment->portfolio_id ?>
        </div>
      </div>
    </div>
  </div>

  <!-- 本体カード -->
  <div class="card border-0 shadow-sm">
    <div class="card-header border-0 bg-gradient-primary text-white rounded-top-3">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-regular fa-comments fa-lg"></i>
        <span class="fw-semibold">コメント内容</span>
      </div>
    </div>

    <div class="card-body p-4">
      <?= $this->Form->create($comment, ['class' => 'needs-validation', 'novalidate' => true]) ?>
        <?= $this->Form->hidden('portfolio_id') ?>

        <label for="comment-content" class="form-label fw-semibold">内容</label>
        <?= $this->Form->textarea('content', [
          'id' => 'comment-content',
          'rows' => 6,
          'placeholder' => '感想や補足を編集…',
          'class' => 'form-control form-control-lg',
          // 'maxlength' => 1000, // もし上限を付けたいときはコメントアウト解除
        ]) ?>
        <div class="d-flex justify-content-between mt-2">
          <small class="text-muted">
            <i class="fa-regular fa-clock me-1"></i>
            最終更新：<?= $comment->modified ? $comment->modified->i18nFormat('yyyy/MM/dd HH:mm') : '—' ?>
          </small>
          <small class="text-muted"><span id="char-count">0</span> 文字</small>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
          <?= $this->Html->link(
              'キャンセル',
              ['controller' => 'Portfolios', 'action' => 'view', $comment->portfolio_id],
              ['class' => 'btn btn-outline-secondary']
          ) ?>
          <?= $this->Form->button('<i class="fa-solid fa-floppy-disk me-1"></i> 更新', [
            'class' => 'btn btn-primary',
            'escapeTitle' => false,
          ]) ?>
        </div>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>

<?php $this->start('css'); ?>
<style>
.bg-gradient-primary{
  background: linear-gradient(90deg, #4a5568, #2d3748);
}
.fade-in{ animation: fadeIn .5s ease-in-out; }
@keyframes fadeIn{ from{opacity:0; transform:translateY(6px)} to{opacity:1; transform:none} }
</style>
<?php $this->end(); ?>

<?php $this->start('script'); ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // 文字数カウンタ & オートリサイズ
  const ta = document.getElementById('comment-content');
  const counter = document.getElementById('char-count');

  const update = () => { counter.textContent = ta.value.length; autoResize(); };
  const autoResize = () => { ta.style.height = 'auto'; ta.style.height = ta.scrollHeight + 'px'; };

  ['input','change'].forEach(ev => ta.addEventListener(ev, update));
  update();
});
</script>
<?php $this->end(); ?>
