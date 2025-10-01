<?php $this->assign('title', 'ダッシュボード') ?>
<div class="row g-3">
  <?php
    $cards = [
      ['title' => 'ユーザー', 'val' => $stats['users'], 'icon' => 'fa-user'],
      ['title' => '企業', 'val' => $stats['companies'], 'icon' => 'fa-building'],
      ['title' => 'ポートフォリオ', 'val' => $stats['portfolios'], 'icon' => 'fa-images'],
      ['title' => 'コメント', 'val' => $stats['comments'], 'icon' => 'fa-comments'],
      ['title' => '未認証企業', 'val' => $stats['pendingCompanies'], 'icon' => 'fa-shield-halved'],
      ['title' => '非公開PF', 'val' => $stats['privatePortfolios'], 'icon' => 'fa-lock'],
    ];
    ?>
  <?php foreach ($cards as $c) : ?>
    <div class="col-12 col-sm-6 col-lg-4">
      <div class="card p-3">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted small"><?= h($c['title']) ?></div>
            <div class="fs-2 fw-bold"><?= (int)$c['val'] ?></div>
          </div>
          <i class="fa-solid <?= h($c['icon']) ?> fa-2x text-secondary"></i>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
