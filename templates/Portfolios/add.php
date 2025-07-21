<div class="container mt-5" style="max-width: 600px;">
  <h2 class="mb-4 text-center">ポートフォリオを投稿</h2>

  <?= $this->Form->create($portfolio, ['type' => 'file']) ?> <!-- ←これ重要 -->

  <div class="mb-3">
    <?= $this->Form->control('title', ['label' => 'タイトル', 'class' => 'form-control']) ?>
  </div>

  <div class="mb-3">
    <?= $this->Form->control('description', [
        'label' => '説明',
        'type' => 'textarea',
        'rows' => 5,
        'class' => 'form-control'
    ]) ?>
  </div>

  <div class="mb-3">
    <?= $this->Form->control('thumbnail_file', [
        'label' => 'サムネイル画像をアップロード',
        'type' => 'file',
        'class' => 'form-control'
    ]) ?>
  </div>

  <div class="mb-3">
    <?= $this->Form->control('link', ['label' => '関連リンク（任意）', 'class' => 'form-control', 'empty' => true]) ?>
  </div>

  <div class="d-grid">
    <?= $this->Form->button('投稿する', ['class' => 'btn btn-success btn-lg']) ?>
  </div>

  <?= $this->Form->end() ?>
</div>
