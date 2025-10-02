<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Company $company
 */
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="container mt-5">

  <!-- ヘッダー（ロゴ＋見出し＋戻る） -->
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <img id="cmp-logo-preview"
        src="<?= h($company->logo_path ?: 'https://placehold.co/100x100?text=Logo') ?>"
        class="shadow-sm border"
        style="width:80px;height:80px;object-fit:cover;background:#fff;border-radius:50%;"
        onerror="this.src='https://placehold.co/100x100?text=Logo'">
      <div>
        <h2 class="mb-1"><?= h($company->name ?: 'Company') ?> <span class="text-muted fs-6">/ 編集</span></h2>
        <div class="text-muted small">
          <i class="fa-regular fa-clock me-1"></i>
          更新：<?= $company->modified ? $company->modified->i18nFormat('yyyy/MM/dd HH:mm') : '—' ?>
        </div>
      </div>
    </div>
    <div class="d-flex gap-2">
      <?= $this->Html->link('一覧へ', ['action' => 'index'], ['class' => 'btn btn-outline-secondary']) ?>
      <?= $this->Html->link('詳細を見る', ['action' => 'view',$company->id], ['class' => 'btn btn-outline-dark']) ?>
    </div>
  </div>

  <div class="row g-4">
    <!-- 左：主要フォーム -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <?= $this->Form->create($company, ['class' => 'needs-validation', 'novalidate' => true, 'type' => 'file']) ?>

          <!-- 会社名 / スラッグ -->
          <div class="row g-3">
            <div class="col-md-7">
              <label class="form-label fw-semibold"><i class="fa-solid fa-building me-2 text-primary"></i>会社名</label>
              <?= $this->Form->text('name', [
                'class' => 'form-control form-control-lg',
                'placeholder' => '例）株式会社OrcaFolio',
                'required' => true,
                'id' => 'cmp-name',
              ]) ?>
            </div>
            <div class="col-md-5">
              <label class="form-label fw-semibold"><i class="fa-solid fa-tag me-2 text-secondary"></i>スラッグ</label>
              <div class="input-group">
                <span class="input-group-text">/c/</span>
                <?= $this->Form->text('slug', [
                  'class' => 'form-control',
                  'placeholder' => '例）OrcaFolio',
                  'id' => 'cmp-slug',
                  'maxlength' => 160,
                ]) ?>
              </div>
              <div class="form-text">空なら会社名から自動生成します。</div>
            </div>
          </div>

          <hr class="my-4">

          <!-- Web / 請求メール -->
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold"><i class="fa-solid fa-globe me-2 text-primary"></i>WebサイトURL</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-link"></i></span>
                <?= $this->Form->text('website', [
                  'class' => 'form-control',
                  'placeholder' => 'https://example.com',
                  'id' => 'cmp-website',
                ]) ?>
              </div>
              <div class="form-text">`http(s)://` から始まるURLを推奨します。</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold"><i class="fa-regular fa-envelope me-2 text-primary"></i>請求メール</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                <?= $this->Form->email('billing_email', [
                  'class' => 'form-control',
                  'placeholder' => 'billing@example.com',
                ]) ?>
              </div>
            </div>
          </div>

          <!-- 業種 / 規模 -->
          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label fw-semibold"><i class="fa-solid fa-industry me-2 text-secondary"></i>業種</label>
              <?= $this->Form->text('industry', ['class' => 'form-control', 'placeholder' => '例）IT・ソフトウェア']) ?>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold"><i class="fa-solid fa-people-group me-2 text-secondary"></i>規模</label>
              <?= $this->Form->text('size', ['class' => 'form-control', 'placeholder' => '例）11-50名']) ?>
            </div>
          </div>

          <!-- ドメイン / ロゴ -->
          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label fw-semibold"><i class="fa-solid fa-at me-2 text-secondary"></i>メールドメイン（任意）</label>
              <div class="input-group">
                <span class="input-group-text">@</span>
                <?= $this->Form->text('domain', [
                  'class' => 'form-control',
                  'placeholder' => '例）example.com',
                  'id' => 'cmp-domain',
                ]) ?>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">
                <i class="fa-regular fa-image me-2 text-secondary"></i>ロゴ（画像アップロード）
              </label>

              <div class="input-group">
                <span class="input-group-text"><i class="fa-regular fa-image"></i></span>
                <?= $this->Form->file('logo_file', [
                  'accept' => 'image/png,image/jpeg,image/webp,image/gif, image/svg+xml', // svgは必要なら
                  'id' => 'cmp-logo-file',
                  'class' => 'form-control',
                ]) ?>
              </div>
              <div class="form-text">PNG / JPG / WEBP / GIF / SVG、最大 2MB 推奨。</div>

              <?php // 既存のパスは維持用（新規アップ時にサーバ側で上書き） ?>
              <?= $this->Form->hidden('logo_path') ?>
            </div>
          </div>

          <!-- 説明 -->
          <div class="mt-3">
            <label class="form-label fw-semibold"><i class="fa-regular fa-file-lines me-2 text-secondary"></i>説明</label>
            <?= $this->Form->textarea('description', [
              'rows' => 5,
              'class' => 'form-control',
              'placeholder' => '事業内容、ミッション、主要プロダクト、技術スタックなど…',
            ]) ?>
          </div>

          <?php /* owner_user_id はフォームに出さない（コントローラで固定） */ ?>

          <div class="mt-4 d-flex gap-2">
            <?= $this->Form->button('<i class="fa-regular fa-floppy-disk me-2"></i>保存する', [
              'escapeTitle' => false,'class' => 'btn btn-primary btn-lg px-4',
            ]) ?>
            <?= $this->Html->link('キャンセル', ['action' => 'view',$company->id], ['class' => 'btn btn-outline-secondary btn-lg px-4']) ?>
          </div>

          <?= $this->Form->end() ?>
        </div>
      </div>
    </div>

    <!-- 右：デンジャーゾーン -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <h5 class="fw-semibold mb-3 text-danger"><i class="fa-regular fa-trash-can me-2"></i>Danger Zone</h5>
          <p class="text-secondary small mb-3">この会社プロフィールを削除します。元に戻せません。</p>
          <?= $this->Form->postLink('削除する', ['action' => 'delete',$company->id], [
            'class' => 'btn btn-outline-danger w-100',
            'confirm' => __('本当に削除しますか？'),
          ]) ?>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* 軽い装飾 */
.needs-validation .form-control:required { background-image:none }
</style>

<script>
// スラッグ自動生成（空のときだけ）
(() => {
  const name = document.getElementById('cmp-name');
  const slug = document.getElementById('cmp-slug');
  if(name && slug){
    const toSlug = s => (s||'').toLowerCase()
      .normalize('NFKD').replace(/[\u0300-\u036f]/g,'')
      .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,160);
    name.addEventListener('input', () => { if(!slug.value) slug.value = toSlug(name.value); });
  }
})();

// website → domain 推測（domain が空のときだけ）
(() => {
  const web = document.getElementById('cmp-website');
  const dom = document.getElementById('cmp-domain');
  if(web && dom){
    web.addEventListener('blur', () => {
      if(dom.value || !web.value) return;
      try{
        const u = new URL(web.value.startsWith('http') ? web.value : 'https://'+web.value);
        dom.value = u.hostname.replace(/^www\./,'');
      }catch(e){}
    });
  }
})();

// ロゴプレビュー反映
(() => {
  const input = document.getElementById('cmp-logo-path');
  const preview = document.getElementById('cmp-logo-preview');
  if(input && preview){
    const render = () => { preview.src = input.value || 'https://placehold.co/100x100?text=Logo'; };
    input.addEventListener('input', render);
  }
})();

// 送信時の簡易バリデーション
(() => {
  const form = document.querySelector('.needs-validation');
  if(!form) return;
  form.addEventListener('submit', (e) => {
    if(!form.checkValidity()){ e.preventDefault(); e.stopPropagation(); }
    form.classList.add('was-validated');
  });
})();
</script>

<script>
(() => {
  const file = document.getElementById('cmp-logo-file');
  const preview = document.getElementById('cmp-logo-preview');
  if (file && preview) {
    file.addEventListener('change', () => {
      const f = file.files?.[0];
      if (!f) return;
      const reader = new FileReader();
      reader.onload = e => { preview.src = e.target.result; };
      reader.readAsDataURL(f);
    });
  }
})();
</script>
