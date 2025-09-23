<?php
$cakeDescription = 'OrcaFolio';
?>
<?php
$isLoggedIn  = $this->Identity->isLoggedIn();
$isEmployer  = $isLoggedIn && $this->Identity->get('auth_email') !== null; // 企業ログイン判定
$companyId   = $isEmployer ? (int)$this->Identity->get('id') : null;
$logoutUrl   = $isEmployer ? '/employer/logout' : '/users/logout';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->fetch('title') ?: $cakeDescription ?></title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <?= $this->Html->meta('csrfToken', $this->request->getAttribute('csrfToken')) ?>

    <style>
    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(to bottom right, #f0f4f8, #ffffff);
    }

    .navbar {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        background: #ffffff;
        transition: background-color 0.3s ease;
    }

    .navbar-brand {
        font-weight: 700;
        font-size: 1.5rem;
    }

    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%280, 0, 0, 0.5%29' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }

    .card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
    }

    .card-body {
        padding: 1rem;
    }

    .btn {
        border-radius: 8px;
        transition: background-color 0.3s, transform 0.2s;
    }
    .btn:hover {
        transform: scale(1.05);
    }

    i.fa-heart.liked {
        color: hotpink;
    }
    i.fa-heart.not-liked {
        color: #ccc;
    }

    .offcanvas-body ul li a {
        display: block;
        padding: 10px;
        border-radius: 6px;
        text-decoration: none;
        color: #333;
        transition: background-color 0.2s;
    }
    .offcanvas-body ul li a:hover {
        background-color: #f0f0f0;
    }
    </style>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body>

<nav class="navbar bg-white shadow-sm px-3 py-2 sticky-top">
  <div class="container-fluid position-relative d-flex align-items-center" style="min-height:56px;">
    
    <!-- 左：メニュー -->
    <div class="nav-left">
      <button class="btn btn-outline-secondary me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-label="メニュー">
        <i class="fas fa-bars"></i>
      </button>
    </div>

    <!-- 中央ロゴ（常に中央固定） -->
    <div class="position-absolute top-50 start-50 translate-middle">
      <a class="navbar-brand fw-bold text-dark m-0" href="<?= $this->Url->build('/') ?>">
        OrcaFolio
      </a>
    </div>

    <!-- 右：ログイン系 -->
    <div class="nav-right ms-auto d-flex align-items-center">
      <?php if ($isLoggedIn): ?>
        <span class="me-2 d-none d-sm-inline text-truncate" style="max-width:140px;">
          ようこそ <?= h($this->Identity->get('name')) ?> さん
        </span>
        <a href="<?= $logoutUrl ?>" class="btn btn-outline-secondary btn-sm d-none d-sm-inline">ログアウト</a>
        <a href="<?= $logoutUrl ?>" class="btn btn-outline-secondary btn-sm d-inline d-sm-none" title="ログアウト">
          <i class="fa-solid fa-right-from-bracket"></i>
        </a>
      <?php else: ?>
        <a href="/users/login" class="btn btn-outline-primary btn-sm me-2 d-none d-sm-inline">ログイン</a>
        <a href="/users/register" class="btn btn-primary btn-sm d-none d-sm-inline">新規登録</a>
        <a href="/users/login" class="btn btn-primary btn-sm d-inline d-sm-none" title="ログイン">
          <i class="fa-solid fa-user"></i>
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>


<div class="offcanvas offcanvas-start custom-offcanvas" tabindex="-1" id="sidebarMenu">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">メニュー</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <ul class="list-unstyled">
        <li><a href="/top">トップ画面</a></li>
        <?php if ($isLoggedIn): ?>
          <?php if ($isEmployer): ?>
            <li>
              <a href="<?= $this->Url->build([
                'prefix' => 'Employer',
                'controller' => 'Companies',
                'action' => 'view',
                $companyId
              ]) ?>">
                会社マイページ
              </a>
            </li>
          <?php else: ?>
            <li>
              <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'profile']) ?>">
                プロフィール
              </a>
            </li>
          <?php endif; ?>
        <?php endif; ?>
        <li><a href="/users/search">ユーザー検索</a></li>
        <li><a href="/favorites">お気に入り</a></li>
        <?php if ($isLoggedIn): ?>
          <?php if ($isEmployer): ?>
            <li>
              <a href="<?= $this->Url->build([
                'prefix' => 'Employer',
                'controller' => 'portfolios',
                'action' => 'add',
                $companyId
              ]) ?>">
                投稿する
              </a>
            </li>
          <?php else: ?>
            <li>
              <a href="<?= $this->Url->build(['controller' => 'portfolios', 'action' => 'add']) ?>">
                投稿する
              </a>
            </li>
          <?php endif; ?>
        <?php endif; ?>
        <li><a href="/conversations">メッセージ</a></li>
        <li>
          <a href="/notifications">
            通知<?= isset($unreadCount) && $unreadCount > 0 ? "（{$unreadCount}）" : '' ?>
          </a>
        </li>
        <?php if ($isLoggedIn): ?>
          <?php if ($isEmployer): ?>
            <li>
              <a href="<?= $this->Url->build([
                'prefix' => 'Employer',
                'controller' => 'Settings',
                'action' => 'index',
                $companyId
              ]) ?>">
                会社アカウント設定
              </a>
            </li>
          <?php else: ?>
            <li>
              <a href="<?= $this->Url->build(['controller' => 'Settings', 'action' => 'index']) ?>">
                設定
              </a>
            </li>
          <?php endif; ?>
        <?php endif; ?>
    </ul>
  </div>
</div>

<style>
.custom-offcanvas {
    width: 240px !important; /* デフォルトより少し細めに */
}

.custom-offcanvas .offcanvas-title {
    font-weight: 600;
    font-size: 1.2rem;
}

.custom-offcanvas ul li a {
    display: block;
    padding: 10px 15px;
    border-radius: 6px;
    color: #333;
    text-decoration: none;
    transition: background-color 0.2s ease;
}

.custom-offcanvas ul li a:hover {
    background-color: #f0f0f0;
}

/* ✅ 3カラム想定の崩れ防止 */
.navbar-3cols { min-height: 56px; } /* タップしやすい高さ確保 */
.nav-left, .nav-right { flex: 0 0 auto; }
.nav-center { flex: 1 1 auto; }

/* スマホでボタンが大きすぎると折り返すので微調整 */
@media (max-width: 576px) {
  .navbar .btn { padding: .4rem .6rem; }
  .navbar .navbar-brand { font-size: 1.125rem; } /* ロゴ少し小さめ */
}
</style>

<main class="container mt-4">
    <?= $this->Flash->render() ?>
    <?= $this->fetch('content') ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?= $this->fetch('script') ?>
<?= $this->Html->css('style') ?>
</body>
</html>
