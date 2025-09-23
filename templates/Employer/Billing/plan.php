<?php $hasSub = !empty($company->stripe_subscription_id); ?>
<div class="container py-4">
<div class="d-flex align-items-center justify-content-between mb-3">
  <h1 class="h4 mb-0">プラン変更</h1>
</div>
<div class="d-flex align-items-center mb-2">
  <p class="text-muted mb-0 flex-grow-1">
    現在のプラン：<strong><?= h($company->plan ?: 'free') ?></strong>
    <?php if (!empty($company->paid_until)): ?>
      <span class="ms-2 small">
        (有効期限：<?= h($company->paid_until->i18nFormat('yyyy-MM-dd HH:mm')) ?>)
      </span>
    <?php endif; ?>
    <?php if ($hasSub && !empty($nextRenewAt)): ?>
      <span class="ms-2 small">
        次回<?= $willAutoRenew ? '自動更新' : '終了予定' ?>：
        <?= h($nextRenewAt->i18nFormat('yyyy-MM-dd HH:mm')) ?>
      </span>
      <?php if ($willAutoRenew === false): ?>
        <span class="badge bg-secondary ms-1 align-middle">期末で解約</span>
      <?php endif; ?>
    <?php endif; ?>
  </p>
  <?php if ($this->Identity->isLoggedIn() && $this->Identity->get('id') === $company->id): ?>
    <div class="ms-2 d-flex align-items-center gap-2">
      <?= $this->Html->link('請求履歴', '/employer/billing/history', [
        'class' => 'btn btn-outline-primary'
      ]) ?>

      <?php if (!empty($company->stripe_subscription_id)): ?>
        <?= $this->Form->postLink('今期末で解約', [
              'prefix' => 'Employer',
              'controller' => 'Billing',
              'action' => 'cancelAtPeriodEnd',
            ], [
              'class' => 'btn btn-outline-danger',
              'confirm' => "今期末で自動更新を停止します。よろしいですか？",
            ]) ?>
        <?= $this->Form->postLink('今すぐ解約', [
              'prefix' => 'Employer',
              'controller' => 'Billing',
              'action' => 'cancelNow',
            ], [
              'class' => 'btn btn-danger',
              'confirm' => "即時にサブスクリプションを解約します。残期間は消滅します。続行しますか？",
            ]) ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
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
