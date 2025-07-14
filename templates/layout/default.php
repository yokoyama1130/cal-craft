<?php
$cakeDescription = 'Calcraft - 機械系エンジニアのためのポートフォリオ';
?>
<!DOCTYPE html>
<html>
<head>
    <!-- Airbnb風フォント -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS（すでにあるならOK） -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f9f9f9;
    }

    .navbar {
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        transition: transform 0.2s ease-in-out;
    }

    .card:hover {
        transform: translateY(-4px);
    }

    .btn-primary, .btn-success {
        border-radius: 8px;
    }
    </style>

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

  <?php if ($this->Identity->isLoggedIn()): ?>
    ようこそ <?= h($this->Identity->get('name')) ?> さん
    <?php else: ?>
    <a href="/users/login">ログイン</a>
    <a href="/users/register">新規登録</a>
    <?php endif; ?>

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
