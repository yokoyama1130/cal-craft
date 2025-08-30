<div class="container py-4">
  <h1 class="h4 mb-3">プラン変更</h1>
  <p class="text-muted">現在のプラン：<strong><?= h($company->plan ?: 'free') ?></strong></p>

  <div class="row g-3">
    <?php foreach ($plans as $key => $p): ?>
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex flex-column">
            <h5 class="fw-bold mb-1"><?= h($p['label']) ?></h5>
            <div class="text-muted mb-2">
              <?= $p['price'] === null ? '要お問い合わせ' : ($p['price'] ? '¥'.number_format($p['price']).'/月' : '無料') ?>
            </div>
            <ul class="small list-unstyled mb-4">
              <?php foreach ($p['features'] as $f): ?>
                <li>・<?= h($f) ?></li>
              <?php endforeach; ?>
            </ul>
            <div class="mt-auto">
              <?php if ($company->plan === $key): ?>
                <button class="btn btn-secondary w-100" disabled>現在のプラン</button>
              <?php else: ?>
                <?= $this->Html->link('選択する', ['prefix'=>'Employer','controller'=>'Billing','action'=>'pay', $key], ['class'=>'btn btn-primary w-100']) ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
