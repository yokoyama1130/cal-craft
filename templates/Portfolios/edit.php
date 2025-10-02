<h2>投稿の編集</h2>

<div class="card p-4 shadow-sm">
  <?= $this->Form->create($portfolio) ?>
  
  <!-- 基本情報 -->
  <div class="mb-3">
    <?= $this->Form->control('title', [
      'label' => 'タイトル',
      'class' => 'form-control',
    ]) ?>
  </div>

  <div class="mb-3">
    <?= $this->Form->control('description', [
      'label' => '概要・紹介文',
      'class' => 'form-control',
      'rows' => 3,
    ]) ?>
  </div>

  <div class="mb-3">
    <?= $this->Form->control('thumbnail', [
      'label' => 'サムネイル画像URL',
      'class' => 'form-control',
    ]) ?>
  </div>

  <div class="mb-3">
    <?= $this->Form->control('link', [
      'label' => '関連リンク（任意）',
      'class' => 'form-control',
      'placeholder' => 'https://...',
    ]) ?>
  </div>

  <!-- ▼ 機械系ジャンルの場合のみ -->
  <?php if (!empty($portfolio->category) && $portfolio->category->slug === 'mechanical') : ?>
    <hr>
    <h5 class="mt-3 mb-2">🔧 機械系 詳細入力</h5>

    <!-- [1] 設計構想・目的 -->
      <?= $this->Form->control('purpose', [
          'label' => '[1] 目的・背景',
          'type' => 'textarea',
          'rows' => 3,
          'class' => 'form-control',
      ]) ?>

      <?= $this->Form->control('basic_spec', [
        'label' => '基本仕様（寸法、目標性能など）',
        'type' => 'textarea',
        'rows' => 2,
        'class' => 'form-control',
      ]) ?>

    <!-- [2] 設計と部品 -->
      <?= $this->Form->control('design_url', [
        'label' => '[2] 設計書リンク（PDF/URL）',
        'class' => 'form-control',
        'placeholder' => 'https://...',
      ]) ?>

      <?= $this->Form->control('design_description', [
        'label' => '設計の説明（CAD・構造など）',
        'type' => 'textarea',
        'rows' => 3,
        'class' => 'form-control',
      ]) ?>

      <?= $this->Form->control('parts_list', [
        'label' => '部品リスト（名称／数量／型番など）',
        'type' => 'textarea',
        'rows' => 3,
        'class' => 'form-control',
      ]) ?>

      <!-- [3] 加工・解析 -->
      <?= $this->Form->control('processing_method', [
        'label' => '[3] 加工方法',
        'class' => 'form-control',
      ]) ?>

      <?= $this->Form->control('processing_notes', [
        'label' => '加工ノウハウ・注意点',
        'type' => 'textarea',
        'rows' => 2,
        'class' => 'form-control',
      ]) ?>

      <?= $this->Form->control('analysis_method', [
        'label' => '解析手法（CAE/手計算など）',
        'class' => 'form-control',
      ]) ?>

      <?= $this->Form->control('analysis_result', [
        'label' => '解析結果・考察',
        'type' => 'textarea',
        'rows' => 3,
        'class' => 'form-control',
      ]) ?>

      <!-- [4] 補足 -->
      <?= $this->Form->control('development_period', [
        'label' => '[4] 開発期間',
        'class' => 'form-control',
      ]) ?>

      <?= $this->Form->control('mechanical_notes', [
        'label' => '工夫点・反省点など',
        'type' => 'textarea',
        'rows' => 3,
        'class' => 'form-control',
      ]) ?>

      <?= $this->Form->control('reference_links', [
        'label' => '参考資料・URL（複数可）',
        'type' => 'textarea',
        'rows' => 2,
        'class' => 'form-control',
        'placeholder' => '例：\nhttps://...\nhttps://...',
      ]) ?>
  <?php endif; ?>

  <!-- 公開設定 -->
  <div class="form-check mb-3 mt-4">
    <?= $this->Form->control('is_public', [
      'type' => 'checkbox',
      'label' => '公開する',
      'class' => 'form-check-input',
      'hiddenField' => true,
    ]) ?>
  </div>

  <?= $this->Form->button('更新', ['class' => 'btn btn-primary']) ?>
  <?= $this->Form->end() ?>
</div>

