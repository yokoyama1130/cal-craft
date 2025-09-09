<?php $hasSub = !empty($company->stripe_subscription_id); ?>
<div class="container py-4">
  <h1 class="h4 mb-3">プラン変更</h1>
  <p class="text-muted">
    現在のプラン：<strong><?= h($company->plan ?: 'free') ?></strong>
    <?php if (!empty($company->paid_until)): ?>
      <span class="ms-2 small">(有効期限：<?= h($company->paid_until->i18nFormat('yyyy-MM-dd HH:mm')) ?>)</span>
    <?php endif; ?>
  </p>

  <div class="row g-3">
    <?php foreach ($plans as $key => $p): ?>
      <div class="col-md-4">
        <div class="card shadow-sm h-100">
          <div class="card-body d-flex flex-column">
            <h5><?= h($p['label']) ?></h5>
            <p class="text-muted"><?= $p['price'] ? '¥'.number_format($p['price']).'/月':'無料' ?></p>
            <ul class="small mb-3">
              <?php foreach ($p['features'] as $f): ?>
                <li><?= h($f) ?></li>
              <?php endforeach; ?>
            </ul>
            <div class="mt-auto">
              <?php if ($company->plan === $key): ?>
                <button class="btn btn-secondary w-100" disabled>現在のプラン</button>
              <?php else: ?>
                <?= $this->Form->create(null, ['url'=>['prefix'=>'Employer','controller'=>'Billing','action'=>'checkout',$key],'method'=>'post']) ?>
                  <?= $this->Form->button(h($p['label']).' を申し込む',['class'=>'btn btn-primary w-100']) ?>
                <?= $this->Form->end() ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
