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
      <div class="col-lg-5 mt-4 mt-lg-0 text-lg-end">
        <?= $this->Html->link('← ' . __('一覧へ'), ['action'=>'index'], ['class'=>'btn btn-outline-secondary btn-lg']) ?>
      </div>
    </div>
  </div>
</div>

<section class="container py-4">
  <div class="card glass border-0 shadow-sm p-3 p-md-4">
    <?= $this->Form->create($company) ?>
      <div class="row g-3">
        <div class="col-md-6">
          <?= $this->Form->control('name', [
            'label' => '会社名', 'placeholder' => '例）Calcraft株式会社', 'required' => true,
            'templates' => ['inputContainer' => '<div class="mb-2">{{content}}</div>']
          ]) ?>
        </div>
        <div class="col-md-6">
          <?= $this->Form->control('slug', [
            'label' => 'スラッグ', 'placeholder' => '例）calcraft',
            'templates' => ['inputContainer' => '<div class="mb-2">{{content}}</div>']
          ]) ?>
          <div class="form-text">空なら会社名から自動生成されます。</div>
        </div>

        <div class="col-md-6">
          <?= $this->Form->control('website', [
            'label' => 'WebサイトURL', 'type' => 'url', 'placeholder' => 'https://example.com',
            'templates' => ['inputContainer' => '<div class="mb-2">{{content}}</div>']
          ]) ?>
        </div>
        <div class="col-md-6">
          <?= $this->Form->control('billing_email', [
            'label' => '請求メール', 'type' => 'email', 'placeholder' => 'billing@example.com',
            'templates' => ['inputContainer' => '<div class="mb-2">{{content}}</div>']
          ]) ?>
        </div>

        <div class="col-md-6">
          <?= $this->Form->control('industry', [
            'label' => '業種', 'placeholder' => '例）IT・ソフトウェア',
            'templates' => ['inputContainer' => '<div class="mb-2">{{content}}</div>']
          ]) ?>
        </div>
        <div class="col-md-6">
          <?= $this->Form->control('size', [
            'label' => '規模', 'placeholder' => '例）11-50名',
            'templates' => ['inputContainer' => '<div class="mb-2">{{content}}</div>']
          ]) ?>
        </div>

        <div class="col-md-6">
          <?= $this->Form->control('domain', [
            'label' => 'メールドメイン（任意）', 'placeholder' => '例）example.com',
            'templates' => ['inputContainer' => '<div class="mb-2">{{content}}</div>']
          ]) ?>
        </div>
        <div class="col-md-6">
          <?= $this->Form->control('logo_path', [
            'label' => 'ロゴパス（任意）', 'placeholder' => '/img/companies/calcraft.png',
            'templates' => ['inputContainer' => '<div class="mb-2">{{content}}</div>']
          ]) ?>
        </div>

        <div class="col-12">
          <?= $this->Form->control('description', [
            'label' => '説明', 'rows' => 5, 'placeholder' => '事業内容やミッションなど…',
            'templates' => ['inputContainer' => '<div class="mb-2">{{content}}</div>']
          ]) ?>
        </div>

        <div class="col-md-6">
          <?= $this->Form->control('plan', [
            'label' => 'プラン', 'type' => 'select',
            'options' => ['free'=>'Free','pro'=>'Pro','enterprise'=>'Enterprise'],
            'default' => 'free',
            'templates' => ['inputContainer' => '<div class="mb-2">{{content}}</div>']
          ]) ?>
        </div>
        <div class="col-md-6 d-flex align-items-center">
          <div class="form-check mt-3">
            <?= $this->Form->control('verified', [
              'type'=>'checkbox', 'label'=>'Verified にする', 'templates' => ['inputContainer' => '{{content}}']
            ]) ?>
            <div class="form-text">認証済み企業として表示します。</div>
          </div>
        </div>

        <?php /* owner_user_id はフォームに出さない（コントローラで自動セット） */ ?>
      </div>

      <div class="mt-3 d-flex gap-2">
        <?= $this->Form->button('<i class="fa-solid fa-square-plus me-2"></i>作成する', ['escapeTitle'=>false,'class'=>'btn btn-primary btn-lg']) ?>
        <?= $this->Html->link('キャンセル', ['action'=>'index'], ['class'=>'btn btn-outline-secondary btn-lg']) ?>
      </div>
    <?= $this->Form->end() ?>
  </div>
</section>

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
<?php $this->end(); ?>
