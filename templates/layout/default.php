<?php
$cakeDescription = 'Calcraft - 機械系エンジニアのためのポートフォリオ';
?>
<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->fetch('title') ?: $cakeDescription ?></title>
    <?= $this->Html->meta('icon') ?>

    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body>

<!-- ハンバーガーナビゲーション -->
<nav class="navbar navbar-light bg-light px-3">
  <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
    <span class="navbar-toggler-icon"></span>
  </button>
  <a class="navbar-brand ms-3" href="<?= $this->Url->build('/') ?>">Calcraft</a>
</nav>

<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">メニュー</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <ul class="list-unstyled">
      <li><a href="/users/profile">プロフィール</a></li>
      <li><a href="/portfolios/search">検索</a></li>
      <li><a href="/favorites">お気に入り</a></li>
      <li><a href="/portfolios/add">投稿</a></li>
      <li><a href="/messages">メッセージ</a></li>
      <li><a href="/notifications">通知</a></li>
    </ul>
  </div>
</div>

<main class="container mt-4">
    <?= $this->Flash->render() ?>
    <?= $this->fetch('content') ?>
</main>

<!-- Bootstrap JS & Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?= $this->fetch('script') ?>
</body>
</html>
