<?php
$cakeDescription = 'Calcraft - 機械系エンジニアのためのポートフォリオ';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <!-- Airbnb風フォント -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f9f9f9;
    }

    .navbar {
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        position: sticky;
        top: 0;
        z-index: 1000;
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

    .btn-primary, .btn-success, .btn-outline-primary {
        border-radius: 8px;
    }
    </style>

    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->fetch('title') ?: $cakeDescription ?></title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body>

<!-- ナビゲーションバー -->
<nav class="navbar bg-white shadow-sm px-4 py-2">
  <div class="container-fluid">

    <!-- 左：ハンバーガーメニュー -->
    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- 中央：Calcraftロゴ -->
    <div class="mx-auto position-absolute start-50 translate-middle-x">
      <a class="navbar-brand fw-bold fs-4 text-dark" href="<?= $this->Url->build('/') ?>">
        Calcraft
      </a>
    </div>

    <!-- 右：ログイン・登録 or ログアウト -->
    <div class="d-flex ms-auto">
      <?php if ($this->Identity->isLoggedIn()): ?>
        <span class="me-2 mt-2">ようこそ <?= h($this->Identity->get('name')) ?> さん</span>
        <a href="/users/logout" class="btn btn-outline-secondary">ログアウト</a>
      <?php else: ?>
        <a href="/users/login" class="btn btn-outline-primary me-2">ログイン</a>
        <a href="/users/register" class="btn btn-primary">新規登録</a>
      <?php endif; ?>
    </div>

  </div>
</nav>

<!-- 左メニュー：オフキャンバス -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">メニュー</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <ul class="list-unstyled">
        <li><a href="<?= $this->Url->build('/') ?>">ホーム</a></li>
        <li><a href="/users/profile">プロフィール</a></li>
        <li><a href="/portfolios/search">検索</a></li>
        <li><a href="/favorites">お気に入り</a></li>
        <li><a href="/portfolios/add">投稿</a></li>
        <li><a href="/messages">メッセージ</a></li>
        <li><a href="/notifications">通知</a></li>
    </ul>
  </div>
</div>

<!-- メインコンテンツ -->
<main class="container mt-4">
    <?= $this->Flash->render() ?>
    <?= $this->fetch('content') ?>
</main>

<!-- Bootstrap JS & Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?= $this->fetch('script') ?>
</body>
</html>
