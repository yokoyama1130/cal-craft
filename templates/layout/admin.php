<!doctype html>
<html lang="ja">
<head>
  <?= $this->Html->charset() ?>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($this->fetch('title') ?: 'Admin') ?> | OrcaFolio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body{background:#f6f7fb;}
    .admin-shell{display:grid;grid-template-columns:260px 1fr;min-height:100vh;}
    .sidebar{background:#111827;color:#cbd5e1;}
    .sidebar a{color:#cbd5e1;text-decoration:none;display:block;padding:.75rem 1rem;border-radius:.5rem}
    .sidebar a.active,.sidebar a:hover{background:#1f2937;color:#fff}
    .brand{font-weight:700;color:#fff}
    .topbar{background:#fff;border-bottom:1px solid #eee}
    .content{padding:1.25rem;}
    .card{border:0;box-shadow:0 4px 14px rgba(15,23,42,.06)}
  </style>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body>
<div class="admin-shell">
  <aside class="sidebar p-3">
    <div class="brand fs-5 mb-3"><i class="fa-solid fa-shield-halved me-2"></i>Admin</div>
    <nav class="d-grid gap-1">
      <?= $this->Html->link(
          '<i class="fa-solid fa-gauge-high me-2"></i>ダッシュボード',
          [
              'prefix' => 'Admin',
              'controller' => 'Dashboard',
              'action' => 'index',
          ],
          [
              'escape' => false,
              'class' => $this->request->getParam('controller') === 'Dashboard' ? 'active' : '',
          ]
      ) ?>
      <?= $this->Html->link(
          '<i class="fa-regular fa-user me-2"></i>ユーザー',
          [
              'prefix' => 'Admin',
              'controller' => 'Users',
              'action' => 'index',
          ],
          ['escape' => false]
      ) ?>
      <?= $this->Html->link(
          '<i class="fa-solid fa-building me-2"></i>企業',
          [
              'prefix' => 'Admin',
              'controller' => 'Companies',
              'action' => 'index',
          ],
          ['escape' => false]
      ) ?>
      <?= $this->Html->link(
          '<i class="fa-regular fa-images me-2"></i>ポートフォリオ',
          [
              'prefix' => 'Admin',
              'controller' => 'Portfolios',
              'action' => 'index',
          ],
          ['escape' => false]
      ) ?>
      <?= $this->Html->link(
          '<i class="fa-regular fa-comments me-2"></i>コメント',
          [
              'prefix' => 'Admin',
              'controller' => 'Comments',
              'action' => 'index',
          ],
          ['escape' => false]
      ) ?>
    </nav>
  </aside>

  <main>
    <div class="topbar d-flex align-items-center justify-content-between px-3 py-2">
      <div class="fw-semibold"><?= h($this->fetch('title') ?: '') ?></div>
        <div class="text-muted small">
            <i class="fa-regular fa-user me-1"></i>
            <?php $idn = $this->request->getAttribute('identity'); ?>
            <?= h($idn ? ($idn->get('name') ?? 'admin') : 'admin') ?>
        </div>
    </div>
    <div class="content container-fluid">
      <?= $this->Flash->render() ?>
      <?= $this->fetch('content') ?>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= $this->fetch('script') ?>
</body>
</html>
