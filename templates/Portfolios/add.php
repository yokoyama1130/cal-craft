<?php
use Cake\Collection\Collection;

/**
 * カテゴリ options / slugMap を安全に用意
 * - 企業側コントローラからは $categoryOptions, $slugMap が来る想定
 * - ユーザー側は $categories=[['id'=>..,'name'=>..,'slug'=>..], ...] が来る想定
 */

// セレクト用 options
if (isset($categoryOptions) && is_array($categoryOptions)) {
    $options = $categoryOptions;
} else {
    // $categories が未定義/null でも空配列で安全に動作
    $options = (new Collection($categories ?? []))
        ->filter(fn($r) => !empty($r['id'] ?? ($r->id ?? null)) && !empty($r['name'] ?? ($r->name ?? null)))
        ->combine(
            fn($r) => $r['id'] ?? $r->id,
            fn($r) => $r['name'] ?? $r->name
        )
        ->toArray();
}

// JS 用 slugMap
if (isset($slugMap) && is_array($slugMap)) {
    $slugMapArr = $slugMap;
} else {
    $slugMapArr = (new Collection($categories ?? []))
        ->filter(fn($r) => !empty($r['id'] ?? ($r->id ?? null)) && !empty($r['slug'] ?? ($r->slug ?? null)))
        ->combine(
            fn($r) => $r['id'] ?? $r->id,
            fn($r) => $r['slug'] ?? $r->slug
        )
        ->toArray();
}

$identity = $this->getRequest()->getAttribute('identity');
$isEmployer = $this->getRequest()->getParam('prefix') === 'Employer';
?>

<?php if ($isEmployer) : ?>
  <div class="alert alert-warning mb-3">会社アカウントとして投稿します</div>
<?php endif; ?>

<div class="container mt-5" style="max-width: 100%;">
  <h2 class="mb-4 text-center">投稿</h2>

  <?= $this->Form->create($portfolio, ['type' => 'file']) ?>

  <?php if (!empty($portfolio->getErrors())) : ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($portfolio->getErrors() as $field => $messages) : ?>
            <?php foreach ((array)$messages as $rule => $msg) : ?>
              <li><?= h(($field === 'owner' ? '' : $field . ': ') . (is_array($msg) ? implode(', ', $msg) : $msg)) ?></li>
            <?php endforeach; ?>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>



  <!-- ▼ カテゴリ選択 -->
  <div class="mb-3">
    <?= $this->Form->control('category_id', [
        'label' => 'ジャンル（カテゴリ）',
        'type' => 'select',
        'options' => $options,
        'empty' => '選択してください',
        'class' => 'form-select',
        'id' => 'category-select',
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
          'class' => 'form-control',
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('thumbnail_file', [
          'label' => 'サムネイル画像をアップロード',
          'type' => 'file',
          'class' => 'form-control',
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('link', [
          'label' => '関連リンク（任意）',
          'class' => 'form-control',
          'placeholder' => 'https://example.com/...',
      ]) ?>
    </div>
  </div>

  <!-- ▼ カテゴリ別の入力欄 -->
  <!-- ▼ 機械系専用入力欄（slug: mechanical） -->
  <div id="extra-mechanical" class="extra-fields d-none mt-4">

    <h5 class="mt-4 mb-2">[1] 設計構想・目的</h5>

    <?= $this->Form->control('purpose', [
      'label' => '目的・背景',
      'type' => 'textarea',
      'rows' => 3,
      'class' => 'form-control',
    ]) ?>

    <?= $this->Form->control('basic_spec', [
      'label' => '基本仕様（サイズ・重量・用途など）',
      'type' => 'textarea',
      'rows' => 3,
      'class' => 'form-control',
    ]) ?>

    <h5 class="mt-4 mb-2">[2] 設計と部品情報</h5>

    <?= $this->Form->control('design_url', [
      'label' => '設計書リンク（Google Drive, GitHubなど）',
      'class' => 'form-control',
    ]) ?>

    <?= $this->Form->control('design_description', [
      'label' => '設計の説明',
      'type' => 'textarea',
      'rows' => 4,
      'class' => 'form-control',
    ]) ?>

    <?= $this->Form->control('parts_list', [
      'label' => '部品リスト（Markdown形式）',
      'type' => 'textarea',
      'rows' => 5,
      'class' => 'form-control',
    ]) ?>

    <h5 class="mt-4 mb-2">[3] 加工・解析情報</h5>

    <?= $this->Form->control('processing_method', [
      'label' => '加工方法',
      'type' => 'textarea',
      'rows' => 3,
      'class' => 'form-control',
    ]) ?>

    <?= $this->Form->control('processing_notes', [
      'label' => '加工ノウハウ・注意点',
      'type' => 'textarea',
      'rows' => 3,
      'class' => 'form-control',
    ]) ?>

    <?= $this->Form->control('analysis_method', [
      'label' => '解析手法',
      'type' => 'textarea',
      'rows' => 3,
      'class' => 'form-control',
    ]) ?>

    <?= $this->Form->control('analysis_result', [
      'label' => '解析結果・考察',
      'type' => 'textarea',
      'rows' => 4,
      'class' => 'form-control',
    ]) ?>

    <h5 class="mt-4 mb-2">[4] 補足情報</h5>

    <!-- ★ ここでの Form->create の二重呼び出しは削除済み！ -->

    <!-- 図面PDF（1枚） -->
    <?= $this->Form->control('drawing_pdf', [
      'type' => 'file',
      'label' => '図面PDF',
      'accept' => 'application/pdf',
    ]) ?>

    <?= $this->Form->control('development_period', [
      'label' => '開発期間',
      'class' => 'form-control',
    ]) ?>

    <?= $this->Form->control('mechanical_notes', [
      'label' => '工夫点・反省',
      'type' => 'textarea',
      'rows' => 3,
      'class' => 'form-control',
    ]) ?>

    <?= $this->Form->control('reference_links', [
      'label' => '参考資料・URL（Markdown可）',
      'type' => 'textarea',
      'rows' => 3,
      'class' => 'form-control',
    ]) ?>

    <div class="mb-3">
      <?= $this->Form->control('tool_used', [
        'label' => '使用ツール（例：SolidWorks, Fusion360 など）',
        'type' => 'textarea',
        'rows' => 3,
        'class' => 'form-control',
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('material_used', [
        'label' => '使用材料（例：アルミ、SUS304など）',
        'type' => 'textarea',
        'rows' => 3,
        'class' => 'form-control',
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('processing_method', [
        'label' => '加工方法（例：旋盤、フライス、3Dプリンタなど）',
        'type' => 'textarea',
        'rows' => 3,
        'class' => 'form-control',
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('analysis_method', [
        'label' => '解析手法（例：FEM、流体解析など）',
        'type' => 'textarea',
        'rows' => 3,
        'class' => 'form-control',
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('development_period', [
        'label' => '開発期間（例：2ヶ月）',
        'class' => 'form-control',
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('design_url', [
        'label' => '設計書の共有リンク（例：Google Drive、GitHubなど）',
        'class' => 'form-control',
        'placeholder' => 'https://drive.google.com/...',
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('design_description', [
        'label' => '設計の説明（設計意図や工夫点など）',
        'type' => 'textarea',
        'rows' => 4,
        'class' => 'form-control',
      ]) ?>
    </div>

    <div class="mb-3">
      <?= $this->Form->control('mechanical_notes', [
        'label' => 'その他の工夫点・反省点など',
        'type' => 'textarea',
        'rows' => 4,
        'class' => 'form-control',
      ]) ?>
    </div>

    <!-- 補足PDF（複数可） -->
    <?= $this->Form->control('supplement_pdfs[]', [
      'type' => 'file',
      'label' => '補足資料PDF（複数可）',
      'multiple' => true,
      'accept' => 'application/pdf',
    ]) ?>
  </div>

  <div id="extra-programming" class="extra-fields d-none">
    <?= $this->Form->control('github_url', [
      'label' => 'GitHub URL',
      'class' => 'form-control',
    ]) ?>
  </div>

  <div id="extra-chemistry" class="extra-fields d-none">
    <?= $this->Form->control('experiment_summary', [
      'label' => '実験の概要',
      'type' => 'textarea',
      'rows' => 3,
      'class' => 'form-control',
    ]) ?>
  </div>

  <div class="d-grid mt-4">
    <?= $this->Form->button('投稿する', ['class' => 'btn btn-success btn-lg']) ?>
  </div>

  <?= $this->Form->end() ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const textareas = document.querySelectorAll('textarea.form-control');
  textareas.forEach(function (textarea) {
    textarea.addEventListener('input', function () {
      this.style.height = 'auto';
      this.style.height = this.scrollHeight + 'px';
    });
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const select       = document.getElementById('category-select');
  const preview      = document.getElementById('template-preview');
  const commonFields = document.getElementById('common-fields');

  const templates = {
    mechanical: "設計図や解析データ、使ったツール、工学的な工夫点などを書くと◎",
    programming: "技術スタック、開発言語、GitHubリンク、工夫点などを紹介！",
    chemistry:   "実験の目的、手順、考察、結果の写真などを添えるとGood！",
  };

  // PHP から安全に渡したマップ
  const slugMap = <?= json_encode($slugMapArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

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
