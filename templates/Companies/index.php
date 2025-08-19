<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Company> $companies
 */
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="hero-wrap position-relative overflow-hidden">
  <div class="hero-bg-gradient position-absolute top-0 start-0 w-100 h-100"></div>
  <svg class="hero-wave position-absolute bottom-0 start-0 w-100" viewBox="0 0 1440 220" preserveAspectRatio="none">
    <path fill="#ffffff" d="M0,192L48,186.7C96,181,192,171,288,149.3C384,128,480,96,576,85.3C672,75,768,85,864,101.3C960,117,1056,139,1152,138.7C1248,139,1344,117,1392,106.7L1440,96L1440,320L0,320Z"/>
  </svg>

  <div class="container position-relative" style="z-index:2;">
    <div class="row align-items-center py-5 py-lg-6">
      <div class="col-lg-7">
        <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis mb-3 px-3 py-2 shadow-sm">企業プロフィール</span>
        <h1 class="display-6 fw-bold lh-sm text-dark mb-3">
          <span class="text-gradient">Companies</span> 一覧
        </h1>
        <p class="lead text-secondary mb-4">あなたの会社プロフィールを作って、スカウトや採用活動の母艦に。</p>
        <div class="d-flex flex-wrap gap-2">
          <?= $this->Html->link('<i class="fa-solid fa-building-circle-arrow-right me-2"></i>自分の会社へ', ['action' => 'my'], ['escape'=>false,'class'=>'btn btn-outline-secondary btn-lg px-4']) ?>
          <?= $this->Html->link('<i class="fa-solid fa-square-plus me-2"></i>新規作成', ['action' => 'add'], ['escape'=>false,'class'=>'btn btn-primary btn-lg px-4']) ?>
        </div>
      </div>
      <div class="col-lg-5 mt-4 mt-lg-0">
        <div class="card glass shadow-sm animate-rise">
          <div class="card-body p-4">
            <div class="d-flex align-items-center mb-3">
              <div class="icon-circle bg-primary-subtle text-primary-emphasis me-3"><i class="fa-solid fa-id-card-clip"></i></div>
              <h5 class="mb-0 fw-semibold">プロフィールが強いと、伝わる</h5>
            </div>
            <p class="text-secondary small mb-0">
              業種・規模・ミッションを明確に。ロゴやWeb、ドメイン認証で信頼感を底上げ。
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<section class="container py-4">
  <div class="card border-0 shadow-sm p-3 p-md-4">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th class="text-nowrap"><?= $this->Paginator->sort('id', '#') ?></th>
            <th><?= $this->Paginator->sort('name','会社名') ?></th>
            <th><?= $this->Paginator->sort('industry','業種') ?></th>
            <th><?= $this->Paginator->sort('website','Web') ?></th>
            <th class="text-nowrap"><?= $this->Paginator->sort('plan','プラン') ?></th>
            <th class="text-nowrap"><?= $this->Paginator->sort('verified','認証') ?></th>
            <th class="text-nowrap"><?= $this->Paginator->sort('modified','更新') ?></th>
            <th class="text-end"><?= __('Actions') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($companies as $c): ?>
            <tr class="animate-fade">
              <td class="text-muted">#<?= $this->Number->format($c->id) ?></td>
              <td>
                <div class="fw-semibold mb-1">
                  <?= $this->Html->link(h($c->name), ['action'=>'view',$c->id], ['class'=>'link-dark link-underline link-underline-opacity-0']) ?>
                </div>
                <div class="small text-secondary">
                  <i class="fa-solid fa-tag me-1"></i><?= h($c->slug) ?>
                </div>
              </td>
              <td class="text-secondary"><?= h($c->industry) ?></td>
              <td class="truncate" title="<?= h($c->website) ?>">
                <?php if ($c->website): ?>
                  <a href="<?= h($c->website) ?>" target="_blank" rel="noopener" class="small">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i><?= h($c->website) ?>
                  </a>
                <?php else: ?>
                  <span class="text-muted small">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php
                  $plan = strtolower((string)$c->plan);
                  $planClass = 'badge-plan-free';
                  if ($plan==='pro') $planClass='badge-plan-pro';
                  if ($plan==='enterprise') $planClass='badge-plan-enterprise';
                ?>
                <span class="badge <?= $planClass ?>"><?= h($c->plan) ?></span>
              </td>
              <td>
                <?php if ((int)$c->verified === 1): ?>
                  <span class="badge badge-verified"><i class="fa-solid fa-shield-check me-1"></i>Verified</span>
                <?php else: ?>
                  <span class="badge badge-unverified"><i class="fa-solid fa-hourglass-half me-1"></i>Pending</span>
                <?php endif; ?>
              </td>
              <td class="text-nowrap small"><?= $c->modified ? $c->modified->i18nFormat('yyyy/MM/dd HH:mm') : '' ?></td>
              <td class="text-end">
                <div class="btn-group btn-group-sm" role="group">
                  <?= $this->Html->link('<i class="fa-regular fa-eye"></i>', ['action'=>'view',$c->id], ['escape'=>false,'class'=>'btn btn-outline-secondary']) ?>
                  <?= $this->Html->link('<i class="fa-regular fa-pen-to-square"></i>', ['action'=>'edit',$c->id], ['escape'=>false,'class'=>'btn btn-primary']) ?>
                  <?= $this->Form->postLink('<i class="fa-regular fa-trash-can"></i>', ['action'=>'delete',$c->id], [
                        'escape'=>false,'class'=>'btn btn-outline-danger',
                        'confirm'=>__('Are you sure you want to delete # {0}?',$c->id)
                      ]) ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-2">
      <div class="text-muted small">
        <?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} of {{count}}')) ?>
      </div>
      <ul class="pagination mb-0">
        <?= $this->Paginator->first('<< ' . __('first')) ?>
        <?= $this->Paginator->prev('< ' . __('previous')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__('next') . ' >') ?>
        <?= $this->Paginator->last(__('last') . ' >>') ?>
      </ul>
    </div>
  </div>
</section>

<?php $this->start('css'); ?>
<style>
.hero-wrap{min-height:52vh;display:flex;align-items:center}
.hero-bg-gradient{background:radial-gradient(1200px 600px at 20% 10%,#fff2bd 0%,rgba(255,242,189,0) 50%), radial-gradient(900px 500px at 85% 20%,#cfe8ff 0%,rgba(207,232,255,0) 60%),linear-gradient(180deg,#fff,#f7f9fc)}
.hero-wave{height:100px}
.text-gradient{background:linear-gradient(90deg,#eab308,#3b82f6);-webkit-background-clip:text;background-clip:text;color:transparent}
.glass{background:rgba(255,255,255,.75);backdrop-filter: blur(6px);border:1px solid rgba(0,0,0,.05)}
.icon-circle{width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%}
.truncate{max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.badge{border-radius:999px;font-weight:600}
.badge-verified{background:#e6f7ee;color:#1f8f4c;border:1px solid #bfe9cf}
.badge-unverified{background:#fff1f0;color:#cf1322;border:1px solid #ffccc7}
.badge-plan-free{background:#f0f5ff;color:#2f54eb;border:1px solid #adc6ff}
.badge-plan-pro{background:#fff7e6;color:#d46b08;border:1px solid #ffd591}
.badge-plan-enterprise{background:#f9f0ff;color:#722ed1;border:1px solid #d3adf7}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
.animate-fade{animation:fadeIn .45s ease both}
@keyframes rise{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.animate-rise{animation:rise .6s ease both}
</style>
<?php $this->end(); ?>
