<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Company $company
 */
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="container mt-5">

  <!-- ヘッダー：ロゴ＋社名＋バッジ＋アクション -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
      <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
      <div class="d-flex align-items-start gap-3">
        <img src="<?= h($company->logo_path ?: 'https://placehold.co/120x120?text=Logo') ?>"
            alt="logo" class="rounded-circle shadow-sm border bg-white"
            style="width:96px;height:96px;object-fit:cover"
            onerror="this.src='https://placehold.co/120x120?text=Logo'">
        <div>
            <h2 class="mb-1">
              <?= h($company->name ?: 'Company') ?>
            </h2>

            <!-- バッジ -->
            <div class="d-flex flex-wrap gap-2">
              <?php
                $plan = strtolower((string)$company->plan);
                $planClass = 'badge-plan-free';
                if ($plan === 'pro') $planClass = 'badge-plan-pro';
                if ($plan === 'enterprise') $planClass = 'badge-plan-enterprise';
              ?>
              <span class="badge <?= $planClass ?>"><?= h($company->plan ?: 'free') ?></span>

              <?php if ((int)$company->verified === 1): ?>
                <span class="badge badge-verified"><i class="fa-solid fa-shield-check me-1"></i>Verified</span>
              <?php else: ?>
                <span class="badge badge-unverified"><i class="fa-regular fa-hourglass-half me-1"></i>Pending</span>
              <?php endif; ?>

              <?php if (!empty($company->industry)): ?>
                <span class="badge bg-light text-secondary border"><i class="fa-solid fa-industry me-1"></i><?= h($company->industry) ?></span>
              <?php endif; ?>

              <?php if (!empty($company->size)): ?>
                <span class="badge bg-light text-secondary border"><i class="fa-solid fa-people-group me-1"></i><?= h($company->size) ?></span>
              <?php endif; ?>
            </div>

            <!-- メタ -->
            <div class="text-muted small mt-2 d-flex flex-wrap gap-3">
              <span><i class="fa-regular fa-clock me-1"></i>更新：<?= $company->modified ? $company->modified->i18nFormat('yyyy/MM/dd HH:mm') : '—' ?></span>
              <span><i class="fa-regular fa-calendar-plus me-1"></i>作成：<?= $company->created ? $company->created->i18nFormat('yyyy/MM/dd HH:mm') : '—' ?></span>
              <span class="text-secondary">ID: #<?= $this->Number->format($company->id) ?></span>
            </div>
          </div>
        </div>

        <!-- アクション -->
        <div class="d-flex gap-2">
          <?php if (!empty($company->slug)): ?>
            <a href="/c/<?= h($company->slug) ?>" target="_blank" class="btn btn-outline-secondary">
              <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> 公開ページを見る
            </a>
          <?php endif; ?>
          <?= $this->Html->link('<i class="fa-regular fa-pen-to-square me-1"></i> 編集', ['action' => 'edit', $company->id], ['escape' => false, 'class' => 'btn btn-primary']) ?>
          <?= $this->Form->postLink('<i class="fa-regular fa-trash-can me-1"></i> 削除', ['action' => 'delete', $company->id], [
                'escape' => false, 'class' => 'btn btn-outline-danger',
                'confirm' => __('本当に削除しますか？')
              ]) ?>
          <?= $this->Html->link('一覧へ', ['action' => 'index'], ['class' => 'btn btn-outline-dark']) ?>
          <?= $this->Html->link('プラン変更', '/employer/billing/plan', ['class'=>'btn btn-outline-primary']) ?>
          <?= $this->Html->link('請求履歴', '/employer/billing/history', ['class'=>'btn btn-outline-primary']) ?>
        </div>
      </div>
    </div>
  </div>

  <!-- 本文：概要カード + 詳細カード -->
  <div class="row g-4">
    <!-- 左：概要・説明 -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
          <h5 class="fw-semibold mb-3"><i class="fa-regular fa-file-lines me-2 text-secondary"></i>会社説明</h5>
          <div class="text-secondary">
            <?php if (!empty($company->description)): ?>
              <?= $this->Text->autoParagraph(h($company->description)); ?>
            <?php else: ?>
              <span class="text-muted">説明はまだ登録されていません。</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if (!empty($company->website)): ?>
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4 d-flex align-items-center justify-content-between">
          <div>
            <h6 class="fw-semibold mb-1"><i class="fa-solid fa-globe me-2 text-primary"></i>Webサイト</h6>
            <a href="<?= h($company->website) ?>" target="_blank" rel="noopener" class="text-decoration-none">
              <?= h($company->website) ?> <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i>
            </a>
          </div>
          <a href="<?= h($company->website) ?>" target="_blank" rel="noopener" class="btn btn-outline-primary">
            開く
          </a>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- 右：詳細・オーナー情報など -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
          <h6 class="fw-semibold mb-3"><i class="fa-solid fa-circle-info me-2 text-secondary"></i>詳細情報</h6>
          <div class="small">
            <div class="d-flex justify-content-between py-1 border-bottom">
              <span class="text-secondary">Slug</span>
              <span class="fw-semibold"><?= h($company->slug ?: '—') ?></span>
            </div>
            <div class="d-flex justify-content-between py-1 border-bottom">
              <span class="text-secondary">Domain</span>
              <span class="fw-semibold"><?= h($company->domain ?: '—') ?></span>
            </div>
            <div class="d-flex justify-content-between py-1 border-bottom">
              <span class="text-secondary">Billing Email</span>
              <span class="fw-semibold"><?= h($company->billing_email ?: '—') ?></span>
            </div>
            <div class="d-flex justify-content-between py-1">
              <span class="text-secondary">Plan</span>
              <span class="fw-semibold"><?= h($company->plan ?: 'free') ?></span>
            </div>
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <h6 class="fw-semibold mb-3"><i class="fa-regular fa-user me-2 text-secondary"></i>オーナー</h6>
          <div class="d-flex align-items-center gap-2">
            <?php if (isset($company->user) && $company->user): ?>
              <?php if (!empty($company->user->icon_url)): ?>
                <img src="<?= h($company->user->icon_url) ?>" class="rounded-circle border shadow-sm" style="width:36px;height:36px;object-fit:cover;">
              <?php endif; ?>
              <?= $this->Html->link(h($company->user->name ?? ('User #'.$company->user->id)), ['controller'=>'Users','action'=>'view',$company->user->id], ['class'=>'text-decoration-none']) ?>
            <?php else: ?>
              <span class="text-muted small">未設定</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php $this->start('css'); ?>
<style>
.badge{border-radius:999px;font-weight:600}
.badge-verified{background:#e6f7ee;color:#1f8f4c;border:1px solid #bfe9cf}
.badge-unverified{background:#fff1f0;color:#cf1322;border:1px solid #ffccc7}
.badge-plan-free{background:#f0f5ff;color:#2f54eb;border:1px solid #adc6ff}
.badge-plan-pro{background:#fff7e6;color:#d46b08;border:1px solid #ffd591}
.badge-plan-enterprise{background:#f9f0ff;color:#722ed1;border:1px solid #d3adf7}
</style>
<?php $this->end(); ?>
