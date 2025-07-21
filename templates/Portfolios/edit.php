<h2>投稿の編集</h2>

<div class="card p-4 shadow-sm">
  <?= $this->Form->create($portfolio) ?>
  
    <div class="mb-3">
      <?= $this->Form->control('title', [
        'label' => 'タイトル',
        'class' => 'form-control'
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('description', [
        'label' => '詳細',
        'class' => 'form-control'
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('thumbnail', [
        'label' => 'サムネイル画像URL',
        'class' => 'form-control'
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('link', [
        'label' => '関連リンク（任意）',
        'class' => 'form-control',
        'placeholder' => 'https://...'
      ]) ?>
    </div>

    <!-- ▼ 機械系ジャンルの投稿だった場合のみ表示 -->
    <?php if (!empty($portfolio->category) && $portfolio->category->slug === 'mechanical'): ?>
      <hr>
      <h5 class="mt-3 mb-2">機械系 詳細情報</h5>

      <?= $this->Form->control('tool_used', [
        'label' => '使用ツール',
        'class' => 'form-control'
      ]) ?>

      <?= $this->Form->control('material_used', [
        'label' => '使用材料',
        'class' => 'form-control'
      ]) ?>

      <?= $this->Form->control('processing_method', [
        'label' => '加工方法',
        'class' => 'form-control'
      ]) ?>

      <?= $this->Form->control('analysis_method', [
        'label' => '解析手法',
        'class' => 'form-control'
      ]) ?>

      <?= $this->Form->control('development_period', [
        'label' => '開発期間',
        'class' => 'form-control'
      ]) ?>

      <?= $this->Form->control('design_url', [
        'label' => '設計書リンク',
        'class' => 'form-control',
        'placeholder' => 'https://...'
      ]) ?>

      <?= $this->Form->control('design_description', [
        'label' => '設計の説明',
        'type' => 'textarea',
        'rows' => 4,
        'class' => 'form-control'
      ]) ?>

      <?= $this->Form->control('mechanical_notes', [
        'label' => '工夫点・反省点など',
        'type' => 'textarea',
        'rows' => 4,
        'class' => 'form-control'
      ]) ?>
    <?php endif; ?>

    <div class="form-check mb-3 mt-4">
      <?= $this->Form->control('is_public', [
        'type' => 'checkbox',
        'label' => '公開する',
        'class' => 'form-check-input',
        'hiddenField' => true
      ]) ?>
    </div>

    <?= $this->Form->button('更新', ['class' => 'btn btn-primary']) ?>
  
  <?= $this->Form->end() ?>
</div>
