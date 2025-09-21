<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Company $company
 */
use Cake\Utility\Text;
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="hero-wrap position-relative overflow-hidden">
  <div class="hero-bg-gradient position-absolute top-0 start-0 w-100 h-100"></div>
  <svg class="hero-wave position-absolute bottom-0 start-0 w-100" viewBox="0 0 1440 220" preserveAspectRatio="none">
    <path fill="#ffffff" d="M0,192L48,186.7C96,181,192,171,288,149.3C384,128,480,96,576,85.3C672,75,768,85,864,101.3C960,117,1056,139,1152,138.7C1248,139,1344,117,1392,106.7L1440,96L1440,320L0,320Z"/>
  </svg>

  <div class="container position-relative" style="z-index:2;">
    <div class="row align-items-center py-5">
      <div class="col-lg-7">
        <h1 class="display-6 fw-bold lh-sm mb-2"><span class="text-gradient">Company</span> を作成</h1>
        <p class="text-secondary mb-0">オーナーはログイン中のユーザーに自動で割当てられます。</p>
      </div>
    </div>
  </div>
</div>

<section class="container py-4">
  <div class="card border-0 shadow-lg glass2 overflow-hidden">
    <div class="row g-0">
      <!-- 左：主要入力 -->
      <div class="col-lg-8 p-4 p-md-5">
        <div class="d-flex align-items-center gap-2 mb-3">
          <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis px-3 py-2 shadow-sm">
            <i class="fa-solid fa-pen-to-square me-1"></i> 新規作成
          </span>
          <h3 class="fw-bold mb-0">会社プロフィール</h3>
        </div>

        <?= $this->Form->create($company, ['class'=>'needs-validation', 'novalidate'=>true, 'type' => 'file']) ?>

        <!-- 会社名 / スラッグ -->
        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label fw-semibold"><i class="fa-solid fa-building me-2 text-primary"></i>会社名</label>
            <?= $this->Form->text('name', [
              'class'=>'form-control form-control-lg',
              'placeholder'=>'例）株式会社OrcaFolio',
              'required'=>true,
              'id'=>'cmp-name'
            ]) ?>
            <div class="form-text">公開名。名刺やWebサイトと表記を統一しましょう。</div>
          </div>
          <div class="col-md-5">
            <label class="form-label fw-semibold"><i class="fa-solid fa-tag me-2 text-secondary"></i>スラッグ</label>
            <div class="input-group">
              <span class="input-group-text">/c/</span>
              <?= $this->Form->text('slug', [
                'class'=>'form-control',
                'placeholder'=>'例）OrcaFolio',
                'id'=>'cmp-slug',
                'maxlength'=>160
              ]) ?>
            </div>
            <div class="form-text">空なら会社名から自動生成します。</div>
          </div>
        </div>

        <hr class="my-4">

        <div class="row g-3 mt-1">
            <div class="col-md-7">
                <label class="form-label fw-semibold">
                <i class="fa-solid fa-user-shield me-2 text-primary"></i>
                オーナー用メール（未ログイン時）
                </label>
                <?= $this->Form->email('owner_email', [
                'class' => 'form-control',
                'placeholder' => 'owner@example.com'
                ]) ?>
                <div class="form-text">未ログインで作成する場合は必須です（ログイン中なら無視されます）。</div>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold">
                <i class="fa-solid fa-key me-2 text-secondary"></i>
                オーナー用パスワード（任意）
                </label>
                <?= $this->Form->password('owner_password', [
                'class' => 'form-control',
                'placeholder' => '未入力なら自動発行'
                ]) ?>
            </div>
        </div>

        <!-- Web / 請求メール -->
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold"><i class="fa-solid fa-globe me-2 text-primary"></i>WebサイトURL</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-link"></i></span>
              <?= $this->Form->text('website', [
                'class'=>'form-control',
                'placeholder'=>'https://example.com',
                'id'=>'cmp-website'
              ]) ?>
            </div>
            <div class="form-text">`http(s)://` から始まるURLを推奨します。</div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold"><i class="fa-solid fa-envelope me-2 text-primary"></i>請求メール</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
              <?= $this->Form->email('billing_email', [
                'class'=>'form-control',
                'placeholder'=>'billing@example.com'
              ]) ?>
            </div>
          </div>
        </div>

        <!-- 業種 / 規模 -->
        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <label class="form-label fw-semibold"><i class="fa-solid fa-industry me-2 text-secondary"></i>業種</label>
            <?= $this->Form->text('industry', [
              'class'=>'form-control',
              'placeholder'=>'例）IT・ソフトウェア'
            ]) ?>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold"><i class="fa-solid fa-people-group me-2 text-secondary"></i>規模</label>
            <?= $this->Form->text('size', [
              'class'=>'form-control',
              'placeholder'=>'例）11-50名'
            ]) ?>
          </div>
        </div>

        <!-- ドメイン / ロゴパス -->
        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <label class="form-label fw-semibold"><i class="fa-solid fa-at me-2 text-secondary"></i>メールドメイン（任意）</label>
            <div class="input-group">
              <span class="input-group-text">@</span>
              <?= $this->Form->text('domain', [
                'class'=>'form-control',
                'placeholder'=>'例）example.com',
                'id'=>'cmp-domain'
              ]) ?>
            </div>
            <div class="form-text">後のドメイン認証で活用できます。</div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold"><i class="fa-solid fa-image me-2 text-secondary"></i>ロゴ（画像アップロード）</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-regular fa-image"></i></span>
              <?= $this->Form->file('logo_file', [
                'accept' => 'image/png,image/jpeg,image/webp,image/gif,image/svg+xml',
                'id' => 'cmp-logo-file',
                'class' => 'form-control'
              ]) ?>
            </div>
            <div class="form-text">PNG/JPG/WEBP/GIF/SVG、最大 2MB 推奨。</div>
            <?= $this->Form->hidden('logo_path') // サーバ側で上書きするために保持 ?>
          </div>
        </div>

        <!-- 説明 -->
        <div class="mt-3">
          <label class="form-label fw-semibold"><i class="fa-regular fa-file-lines me-2 text-secondary"></i>説明</label>
          <?= $this->Form->textarea('description', [
            'rows'=>5,
            'class'=>'form-control',
            'placeholder'=>'事業内容、ミッション、主要プロダクト、技術スタックなど…'
          ]) ?>
        </div>

        <div class="mt-4 d-flex gap-2">
          <?= $this->Form->button('<i class="fa-solid fa-square-plus me-2"></i>作成する', [
            'escapeTitle'=>false,'class'=>'btn btn-gradient btn-lg px-4'
          ]) ?>
          <?= $this->Html->link('キャンセル', ['action'=>'index'], ['class'=>'btn btn-outline-secondary btn-lg px-4']) ?>
        </div>

        <?= $this->Form->end() ?>
      </div>

      <!-- 右：ロゴ/プラン/認証 -->
      <div class="col-lg-4 p-4 p-md-5 bg-light-subtle">
        <!-- ロゴプレビュー -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-body text-center">
            <h6 class="fw-semibold mb-3">
              <i class="fa-regular fa-image me-2 text-primary"></i>ロゴプレビュー
            </h6>
            <div class="logo-preview-wrap rounded-circle d-flex align-items-center justify-content-center mx-auto">
              <img id="cmp-logo-preview"
                  src="<?= h($company->logo_path ?? '') ?>"
                  alt="logo preview"
                  class="rounded-circle"
                  onerror="this.src='https://placehold.co/240x240?text=Logo';">
            </div>
            <div class="form-text mt-2">ロゴパスを入力すると自動プレビューします。</div>
          </div>
        </div>

        <!-- プラン -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="fa-solid fa-crown me-2 text-warning"></i>プラン</h6>
            <div class="btn-group w-100" role="group">
              <button type="button" class="btn btn-outline-primary plan-btn" data-value="free">Free</button>
              <button type="button" class="btn btn-outline-primary plan-btn" data-value="pro">Pro</button>
              <button type="button" class="btn btn-outline-primary plan-btn" data-value="enterprise">Enterprise</button>
            </div>
            <!-- 実体は元のフォームのselect -->
            <input type="hidden" id="cmp-plan-hidden" value="<?= h($company->plan ?? 'free') ?>">
          </div>
        </div>

        <!-- 認証 -->
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="fa-solid fa-shield-check me-2 text-success"></i>認証</h6>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="cmp-verified-switch">
              <label class="form-check-label" for="cmp-verified-switch">Verified にする</label>
            </div>
            <div class="form-text mt-2">ドメイン確認後にONにするのがおすすめです。</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php $this->start('css'); ?>
<style>
.glass2{background:linear-gradient(180deg,#fff,rgba(255,255,255,.96));backdrop-filter:blur(6px);border:1px solid rgba(0,0,0,.05);border-radius:18px}
.btn-gradient{background:linear-gradient(90deg,#3b82f6,#22c55e);color:#fff;border:none}
.btn-gradient:hover{opacity:.92;color:#fff}
.logo-preview-wrap{width:100%;height:120px;border:1px dashed #d9d9d9;border-radius:12px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#fff}
#cmp-logo-preview{max-height:100%;max-width:100%;object-fit:contain}
</style>
<?php $this->end(); ?>

<?php $this->start('script'); ?>
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

// website から domain 推測（空のときだけ）
(() => {
  const web = document.getElementById('cmp-website');
  const dom = document.getElementById('cmp-domain');
  if(web && dom){
    web.addEventListener('blur', () => {
      if(dom.value || !web.value) return;
      try{
        const u = new URL(web.value.startsWith('http') ? web.value : 'https://'+web.value);
        const host = u.hostname.replace(/^www\./,'');
        if(/^[a-z0-9.-]+$/.test(host)) dom.value = host;
      }catch(e){}
    });
  }
})();

// ロゴプレビュー
(() => {
  const path = document.getElementById('cmp-logo-path');
  const img = document.getElementById('cmp-logo-preview');
  if(path && img){
    const render = () => { img.src = path.value || 'https://placehold.co/240x120?text=Logo'; };
    path.addEventListener('input', render);
  }
})();

// プラン ボタングループと hidden select 同期
(() => {
  const hidden = document.getElementById('cmp-plan-hidden');
  const planSelect = document.getElementById('plan'); // Cakeのselect
  const btns = document.querySelectorAll('.plan-btn');
  const apply = (val) => {
    hidden.value = val;
    if(planSelect){ planSelect.value = val; }
    btns.forEach(b => b.classList.toggle('active', b.dataset.value===val));
  };
  btns.forEach(b => b.addEventListener('click', () => apply(b.dataset.value)));
  apply(hidden.value || (planSelect ? planSelect.value : 'free'));
})();

// Verified スイッチと hidden checkbox 同期
(() => {
  const sw = document.getElementById('cmp-verified-switch');
  const chk = document.getElementById('verified'); // Cakeのcheckbox
  if(sw && chk){
    const sync = (fromSwitch) => {
      if(fromSwitch){ chk.checked = sw.checked; }
      else { sw.checked = chk.checked; }
    };
    sw.addEventListener('change', () => sync(true));
    sync(false);
  }
})();

// Bootstrap の HTML5 バリデーション風
(() => {
  const forms = document.querySelectorAll('.needs-validation');
  Array.from(forms).forEach(f=>{
    f.addEventListener('submit',e=>{
      if(!f.checkValidity()){ e.preventDefault(); e.stopPropagation(); }
      f.classList.add('was-validated');
    });
  });
})();
</script>
<?php $this->end(); ?>


<?php $this->start('css'); ?>
<style>
.hero-wrap{min-height:48vh;display:flex;align-items:center}
.hero-bg-gradient{background:radial-gradient(1200px 600px at 20% 10%,#fff2bd 0%,rgba(255,242,189,0) 50%), radial-gradient(900px 500px at 85% 20%,#cfe8ff 0%,rgba(207,232,255,0) 60%),linear-gradient(180deg,#fff,#f7f9fc)}
.hero-wave{height:100px}
.text-gradient{background:linear-gradient(90deg,#eab308,#3b82f6);-webkit-background-clip:text;background-clip:text;color:transparent}
.glass{background:rgba(255,255,255,.75);backdrop-filter: blur(6px);border:1px solid rgba(0,0,0,.05)}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
.animate-rise{animation:fadeIn .6s ease both}
</style>
<?php $this->end(); ?>

<?php $this->start('script'); ?>
<script>
// slug 自動生成（slugが空の時だけ）
(() => {
  const name = document.getElementById('name');
  const slug = document.getElementById('slug');
  if(!name || !slug) return;
  const toSlug = (s) => s
    .toLowerCase()
    .normalize('NFKD').replace(/[\u0300-\u036f]/g,'')
    .replace(/[^a-z0-9]+/g,'-')
    .replace(/^-+|-+$/g,'')
    .substring(0,160);
  name.addEventListener('input', () => { if(!slug.value) slug.value = toSlug(name.value || ''); });
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
<?php $this->end(); ?>
