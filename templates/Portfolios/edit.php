<h2>投稿の編集</h2>

<div class="card p-4 shadow-sm">
  <?= $this->Form->create($portfolio) ?>
    <div class="mb-3">
      <?= $this->Form->control('title', ['label' => 'タイトル', 'class' => 'form-control']) ?>
    </div>
    <div class="mb-3">
      <?= $this->Form->control('description', ['label' => '詳細', 'class' => 'form-control']) ?>
    </div>
    <div class="mb-3">
      <?= $this->Form->control('thumbnail', ['label' => 'サムネイル画像URL', 'class' => 'form-control']) ?>
    </div>
    <div class="mb-3">
      <?= $this->Form->control('link', [
        'label' => '関連リンク（任意）',
        'class' => 'form-control',
        'placeholder' => 'https://...'
      ]) ?>
    </div>
    <div class="form-check mb-3">
      <?= $this->Form->control('is_public', [
        'type' => 'checkbox',
        'label' => '公開する',
        'class' => 'form-check-input',
      ]) ?>
    </div>
    <?= $this->Form->button('更新', ['class' => 'btn btn-primary']) ?>
  <?= $this->Form->end() ?>
</div>
