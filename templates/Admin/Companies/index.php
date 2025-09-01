<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\ORM\ResultSet $companies
 * @var array $q
 */
$this->assign('title', '企業管理');

$val = function($arr, $key, $default=''){ return isset($arr[$key]) ? $arr[$key] : $default; };
?>
<div class="card p-3 mb-3">
  <?= $this->Form->create(null, ['type' => 'get', 'class' => 'row g-2 align-items-end']) ?>
    <div class="col-lg-5">
      <?= $this->Form->control('q', [
        'label' => 'キーワード（社名/ドメイン）',
        'value' => $val($q,'q'),
        'class' => 'form-control',
        'placeholder' => '例）Acme / acme.co'
      ]) ?>
    </div>
    <div class="col-lg-3">
      <?= $this->Form->control('plan', [
        'label' => 'プラン',
        'type'  => 'select',
        'options' => ['' => '—', 'free' => 'free', 'pro' => 'pro', 'enterprise' => 'enterprise'],
        'value' => $val($q,'plan'),
        'class' => 'form-select'
      ]) ?>
    </div>
    <div class="col-lg-2">
      <?= $this->Form->control('verified', [
        'label' => '認証',
        'type'  => 'select',
        'options' => ['' => '—', '1' => 'Verified', '0' => 'Pending'],
        'value' => $val($q,'verified'),
        'class' => 'form-select'
      ]) ?>
    </div>
    <div class="col-lg-2 text-end">
      <button class="btn btn-primary w-100"><i class="fa-solid fa-magnifying-glass me-1"></i>検索</button>
    </div>
  <?= $this->Form->end() ?>
</div>

<div class="card p-0">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width:80px;">ID</th>
          <th style="width:72px;">ロゴ</th>
          <th>企業名 / ドメイン</th>
          <th style="width:160px;">プラン</th>
          <th style="width:140px;">認証</th>
          <th style="width:200px;">Billing Email</th>
          <th style="width:170px;">作成</th>
          <th style="width:280px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($companies as $co): ?>
          <?php
            $plan = strtolower((string)$co->plan);
            $planBadgeClass = 'text-bg-secondary';
            if ($plan === 'pro') $planBadgeClass = 'text-bg-warning';
            if ($plan === 'enterprise') $planBadgeClass = 'text-bg-purple';
            if ($plan === 'free') $planBadgeClass = 'text-bg-info';

            $verified = (int)$co->verified === 1;
          ?>
          <tr>
            <td>#<?= (int)$co->id ?></td>
            <td>
              <?php if (!empty($co->logo_path)): ?>
                <div style="width:48px;height:48px;border-radius:50%;overflow:hidden;border:1px solid #eee">
                  <img src="<?= h($co->logo_path) ?>" style="width:100%;height:100%;object-fit:cover;display:block">
                </div>
              <?php else: ?>
                <div class="text-muted small">—</div>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-semibold"><?= h($co->name) ?></div>
              <div class="text-muted small"><?= h($co->domain ?: '—') ?></div>
            </td>
            <td>
              <span class="badge <?= h($planBadgeClass) ?>"><?= h($co->plan ?: 'free') ?></span>
            </td>
            <td>
              <?php if ($verified): ?>
                <span class="badge text-bg-success"><i class="fa-solid fa-shield-check me-1"></i>Verified</span>
              <?php else: ?>
                <span class="badge text-bg-secondary"><i class="fa-regular fa-hourglass-half me-1"></i>Pending</span>
              <?php endif; ?>
            </td>
            <td><?= h($co->billing_email ?: '—') ?></td>
            <td><?= $co->created ? $co->created->i18nFormat('yyyy/MM/dd HH:mm') : '—' ?></td>
            <td class="text-end">
              <?= $this->Html->link('表示', ['prefix' => false, 'controller' => 'Companies', 'action' => 'view', $co->id], ['class' => 'btn btn-sm btn-outline-secondary']) ?>

              <?php if (!$verified): ?>
                <?= $this->Form->postLink('Verify', ['action' => 'verify', $co->id], ['class' => 'btn btn-sm btn-success ms-1']) ?>
              <?php else: ?>
                <button class="btn btn-sm btn-success ms-1" disabled>Verified</button>
              <?php endif; ?>

              <div class="btn-group ms-1">
                <button class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                  プラン変更
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><?= $this->Form->postLink('free', ['action' => 'plan', $co->id, 'free'], ['class' => 'dropdown-item']) ?></li>
                  <li><?= $this->Form->postLink('pro', ['action' => 'plan', $co->id, 'pro'], ['class' => 'dropdown-item']) ?></li>
                  <li><?= $this->Form->postLink('enterprise', ['action' => 'plan', $co->id, 'enterprise'], ['class' => 'dropdown-item']) ?></li>
                </ul>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="p-3">
    <?= $this->element('pagination') ?>
  </div>
</div>

<?php $this->start('css'); ?>
<style>
.text-bg-purple{ background:#f3e8ff; color:#6b21a8; }
</style>
<?php $this->end(); ?>
