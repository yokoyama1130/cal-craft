<?php
use Cake\Collection\Collection;

$options = (new Collection($categories))->combine('id', 'name')->toArray();
$slugMap = (new Collection($categories))->combine('id', 'slug')->toArray();
?>

<div class="container mt-5" style="max-width: 600px;">
  <h2 class="mb-4 text-center">ポートフォリオを投稿</h2>

  <?= $this->Form->create($portfolio, ['type' => 'file']) ?>

  <!-- ▼ カテゴリ選択 -->
  <div class="mb-3">
    <?= $this->Form->control('category_id', [
        'label' => 'ジャンル（カテゴリ）',
        'type' => 'select',
        'options' => $options,
        'empty' => '選択してください',
        'class' => 'form-select',
        'id' => 'category-select'
    ]) ?>
  </div>

  <!-- ▼ テンプレ説明 -->
  <div class="mb-3">
    <div id="template-preview" class="alert alert-info d-none"></div>
  </div>

  <!-- ▼ 共通入力欄はジャンル選択後に出す -->
  <div id="common-fields" class="d-none">
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
  </div>

  <!-- ▼ カテゴリ別の入力欄 -->
  <!-- ▼ 機械系専用入力欄（slug: mechanical） -->
  <div id="extra-mechanical" class="extra-fields d-none mt-4">

  <!-- ▼ 設計コンセプト -->
  <h5 class="mt-4 mb-2">[1] 設計構想・目的</h5>

  <?= $this->Form->control('purpose', [
    'label' => '目的・背景',
    'type' => 'textarea',
    'rows' => 3,
    'class' => 'form-control'
  ]) ?>

  <?= $this->Form->control('basic_spec', [
    'label' => '基本仕様（サイズ・重量・用途など）',
    'type' => 'textarea',
    'rows' => 3,
    'class' => 'form-control'
  ]) ?>

  <!-- ▼ 設計・部品 -->
  <h5 class="mt-4 mb-2">[2] 設計と部品情報</h5>

  <?= $this->Form->control('design_url', [
    'label' => '設計書リンク（Google Drive, GitHubなど）',
    'class' => 'form-control'
  ]) ?>

  <?= $this->Form->control('design_description', [
    'label' => '設計の説明',
    'type' => 'textarea',
    'rows' => 4,
    'class' => 'form-control'
  ]) ?>

  <?= $this->Form->control('parts_list', [
    'label' => '部品リスト（Markdown形式）',
    'type' => 'textarea',
    'rows' => 5,
    'class' => 'form-control'
  ]) ?>

  <!-- ▼ 加工・解析 -->
  <h5 class="mt-4 mb-2">[3] 加工・解析情報</h5>

  <?= $this->Form->control('processing_method', [
    'label' => '加工方法',
    'class' => 'form-control'
  ]) ?>

  <?= $this->Form->control('processing_notes', [
    'label' => '加工ノウハウ・注意点',
    'type' => 'textarea',
    'rows' => 3,
    'class' => 'form-control'
  ]) ?>

  <?= $this->Form->control('analysis_method', [
    'label' => '解析手法',
    'class' => 'form-control'
  ]) ?>

  <?= $this->Form->control('analysis_result', [
    'label' => '解析結果・考察',
    'type' => 'textarea',
    'rows' => 4,
    'class' => 'form-control'
  ]) ?>

  <!-- ▼ 補足 -->
  <h5 class="mt-4 mb-2">[4] 補足情報</h5>

  <?= $this->Form->control('development_period', [
    'label' => '開発期間',
    'class' => 'form-control'
  ]) ?>

  <?= $this->Form->control('mechanical_notes', [
    'label' => '工夫点・反省',
    'type' => 'textarea',
    'rows' => 3,
    'class' => 'form-control'
  ]) ?>

  <?= $this->Form->control('reference_links', [
    'label' => '参考資料・URL（Markdown可）',
    'type' => 'textarea',
    'rows' => 3,
    'class' => 'form-control'
  ]) ?>

    <div class="mb-3">
      <?= $this->Form->control('tool_used', [
        'label' => '使用ツール（例：SolidWorks, Fusion360 など）',
        'class' => 'form-control'
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('material_used', [
        'label' => '使用材料（例：アルミ、SUS304など）',
        'class' => 'form-control'
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('processing_method', [
        'label' => '加工方法（例：旋盤、フライス、3Dプリンタなど）',
        'class' => 'form-control'
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('analysis_method', [
        'label' => '解析手法（例：FEM、流体解析など）',
        'class' => 'form-control'
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('development_period', [
        'label' => '開発期間（例：2ヶ月）',
        'class' => 'form-control'
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('design_url', [
        'label' => '設計書の共有リンク（例：Google Drive、GitHubなど）',
        'class' => 'form-control',
        'placeholder' => 'https://drive.google.com/...'
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('design_description', [
        'label' => '設計の説明（設計意図や工夫点など）',
        'type' => 'textarea',
        'rows' => 4,
        'class' => 'form-control'
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('mechanical_notes', [
        'label' => 'その他の工夫点・反省点など',
        'type' => 'textarea',
        'rows' => 4,
        'class' => 'form-control'
      ]) ?>
    </div>
  </div>

  <div id="extra-programming" class="extra-fields d-none">
    <?= $this->Form->control('github_url', [
      'label' => 'GitHub URL',
      'class' => 'form-control'
    ]) ?>
  </div>

  <div id="extra-chemistry" class="extra-fields d-none">
    <?= $this->Form->control('experiment_summary', [
      'label' => '実験の概要',
      'type' => 'textarea',
      'rows' => 3,
      'class' => 'form-control'
    ]) ?>
  </div>

  <div class="d-grid mt-4">
    <?= $this->Form->button('投稿する', ['class' => 'btn btn-success btn-lg']) ?>
  </div>

  <?= $this->Form->end() ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const select = document.getElementById('category-select');
  const preview = document.getElementById('template-preview');
  const commonFields = document.getElementById('common-fields');

  const templates = {
    mechanical: "設計図や解析データ、使ったツール、工学的な工夫点などを書くと◎",
    programming: "技術スタック、開発言語、GitHubリンク、工夫点などを紹介！",
    chemistry: "実験の目的、手順、考察、結果の写真などを添えるとGood！",
    // ...（他も必要に応じて追加）
  };

  const slugMap = <?= json_encode($slugMap) ?>;

  function hideAllExtraFields() {
    document.querySelectorAll('.extra-fields').forEach(el => el.classList.add('d-none'));
  }

  select.addEventListener('change', function () {
    const selectedId = this.value;
    const slug = slugMap[selectedId];

    // テンプレ表示
    if (slug && templates[slug]) {
      preview.innerText = templates[slug];
      preview.classList.remove('d-none');
    } else {
      preview.innerText = '';
      preview.classList.add('d-none');
    }

    // 共通欄表示
    if (selectedId) {
      commonFields.classList.remove('d-none');
    } else {
      commonFields.classList.add('d-none');
    }

    // カテゴリ固有欄切り替え
    hideAllExtraFields();
    const extra = document.getElementById(`extra-${slug}`);
    if (extra) {
      extra.classList.remove('d-none');
    }
  });
});
</script>

