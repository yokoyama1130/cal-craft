<!-- templates/Top/index.php -->
<div class="hero-wrap position-relative overflow-hidden">
  <!-- 背景の装飾 -->
  <div class="hero-bg-gradient position-absolute top-0 start-0 w-100 h-100"></div>
  <svg class="hero-wave position-absolute bottom-0 start-0 w-100" viewBox="0 0 1440 220" preserveAspectRatio="none">
    <path fill="#ffffff" d="M0,192L48,186.7C96,181,192,171,288,149.3C384,128,480,96,576,85.3C672,75,768,85,864,101.3C960,117,1056,139,1152,138.7C1248,139,1344,117,1392,106.7L1440,96L1440,320L0,320Z"/>
  </svg>

  <div class="container position-relative" style="z-index:2;">
    <div class="row align-items-center py-5 py-lg-6">
      <div class="col-lg-6">
        <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis mb-3 px-3 py-2 shadow-sm">
          特典資料 配布中
        </span>
        <h1 class="display-5 fw-bold lh-sm text-dark mb-3">
          <span class="text-gradient">機械系エンジニア</span>のための<br>
          ポートフォリオ＆マッチング
        </h1>
        <p class="lead text-secondary mb-4">
          設計・解析・加工の成果物を、美しく・正しく・伝わる形で公開。<br class="d-none d-md-inline">
          企業・エンジニア間の発見とマッチングを加速します。
        </p>

        <div class="d-flex flex-wrap gap-2">
          <?php if ($this->Identity->isLoggedIn()): ?>
            <a href="/portfolios/add" class="btn btn-primary btn-lg px-4">
              <i class="fa-solid fa-rocket-launch me-2"></i>作品を投稿する
            </a>
            <a href="/users/search" class="btn btn-outline-secondary btn-lg px-4">
              <i class="fa-solid fa-users-viewfinder me-2"></i>エンジニアを探す
            </a>
          <?php else: ?>
            <a href="/users/register" class="btn btn-warning text-dark btn-lg px-4">
              <i class="fa-solid fa-user-plus me-2"></i>新規登録（無料）
            </a>
            <a href="/users/login" class="btn btn-outline-dark btn-lg px-4">
              ログイン
            </a>
          <?php endif; ?>
        </div>

        <div class="d-flex align-items-center gap-3 mt-4 text-secondary small">
          <i class="fa-solid fa-shield-check"></i> 認証メール対応
          <i class="fa-solid fa-lock"></i> プライバシー配慮
          <i class="fa-solid fa-bolt"></i> いいね・コメント・フォロー
        </div>
      </div>

      <div class="col-lg-6 mt-5 mt-lg-0">
        <!-- メインのイラスト風カード群 -->
        <div class="hero-boards d-grid gap-3">
          <div class="card glass shadow-sm animate-rise">
            <div class="card-body p-4">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-circle bg-primary-subtle text-primary-emphasis me-3">
                  <i class="fa-solid fa-cubes"></i>
                </div>
                <h5 class="mb-0 fw-semibold">設計ポートフォリオ</h5>
              </div>
              <p class="text-secondary mb-0 small">
                CADモデル、図面、設計意図を一括で管理。<br>基本仕様・部品表・設計書URLも添付可能。
              </p>
            </div>
          </div>
          <div class="card glass shadow-sm animate-rise delay-1">
            <div class="card-body p-4">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-circle bg-success-subtle text-success-emphasis me-3">
                  <i class="fa-solid fa-gears"></i>
                </div>
                <h5 class="mb-0 fw-semibold">加工・解析の知見共有</h5>
              </div>
              <p class="text-secondary mb-0 small">
                加工方法やノウハウ、FEM/CFDなどの解析結果を整理。<br>トラブルと解決の記録で再現性UP。
              </p>
            </div>
          </div>
          <div class="card glass shadow-sm animate-rise delay-2">
            <div class="card-body p-4">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-circle bg-warning-subtle text-warning-emphasis me-3">
                  <i class="fa-solid fa-handshake-angle"></i>
                </div>
                <h5 class="mb-0 fw-semibold">マッチング</h5>
              </div>
              <p class="text-secondary mb-0 small">
                いいね/コメント/DM/フォローでつながる。<br>企業・学生・個人開発者の出会いを後押し。
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 信頼ロゴ（仮） -->
    <div class="row align-items-center g-3 mt-4">
      <div class="col-auto text-secondary small">導入・活用例</div>
      <div class="col">
        <div class="d-flex flex-wrap gap-4 align-items-center opacity-75">
          <img src="https://placehold.co/100x28/png" class="brand" alt="">
          <img src="https://placehold.co/100x28/png" class="brand" alt="">
          <img src="https://placehold.co/100x28/png" class="brand" alt="">
          <img src="https://placehold.co/100x28/png" class="brand" alt="">
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 3ステップ -->
<section class="container py-5">
  <div class="text-center mb-5">
    <h2 class="fw-bold">使い方は、かんたん 3 ステップ</h2>
    <p class="text-secondary mb-0">登録 → 作品投稿 → 反応を見る（いいね/コメント/DM）</p>
  </div>

  <div class="row g-4">
    <div class="col-md-4">
      <div class="step-card h-100 animate-fade">
        <div class="num">1</div>
        <h5 class="fw-semibold mb-2">無料登録</h5>
        <p class="text-secondary small mb-0">メール認証で安心。プロフィールとアイコンを設定しよう。</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="step-card h-100 animate-fade delay-1">
        <div class="num">2</div>
        <h5 class="fw-semibold mb-2">作品を投稿</h5>
        <p class="text-secondary small mb-0">サムネ・概要・設計書URL・部品表・解析結果などを添付。</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="step-card h-100 animate-fade delay-2">
        <div class="num">3</div>
        <h5 class="fw-semibold mb-2">反応＆つながり</h5>
        <p class="text-secondary small mb-0">いいね・コメントで学び合い、DM/フォローで関係構築。</p>
      </div>
    </div>
  </div>
</section>

<!-- 機能カード -->
<section class="bg-light py-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="feature-card h-100">
          <div class="icon-lg bg-primary-subtle text-primary-emphasis"><i class="fa-solid fa-layer-group"></i></div>
          <h5 class="fw-semibold mt-3">カテゴリ最適化済み</h5>
          <p class="text-secondary small mb-3">「機械系」には目的/仕様/設計/加工/解析など専用項目を用意。</p>
          <a href="/portfolios/add" class="link-underline link-underline-opacity-0">今すぐ投稿する →</a>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="feature-card h-100">
          <div class="icon-lg bg-success-subtle text-success-emphasis"><i class="fa-solid fa-chart-mixed"></i></div>
          <h5 class="fw-semibold mt-3">見やすい詳細ページ</h5>
          <p class="text-secondary small mb-3">長文も読みやすいセクション構成＆軽快なアニメーション。</p>
          <a href="/portfolios/search" class="link-underline link-underline-opacity-0">みんなの作品を見る →</a>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="feature-card h-100">
          <div class="icon-lg bg-warning-subtle text-warning-emphasis"><i class="fa-solid fa-message-lines"></i></div>
          <h5 class="fw-semibold mt-3">コミュニケーション</h5>
          <p class="text-secondary small mb-3">いいね・コメント・DM・フォローで、縦にも横にもつながる。</p>
          <a href="/conversations" class="link-underline link-underline-opacity-0">メッセージを見る →</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="container py-5">
  <div class="cta card border-0 shadow-sm p-4 p-md-5 text-center">
    <h3 class="fw-bold mb-3">まずは 1 作品、投稿してみよう。</h3>
    <p class="text-secondary mb-4">設計意図や工夫点、加工ノウハウまで。あなたの“つくる力”が伝わる場所。</p>
    <?php if ($this->Identity->isLoggedIn()): ?>
      <a href="/portfolios/add" class="btn btn-primary btn-lg px-5"><i class="fa-solid fa-plus me-2"></i>投稿する</a>
    <?php else: ?>
      <a href="/users/register" class="btn btn-warning text-dark btn-lg px-5"><i class="fa-solid fa-user-plus me-2"></i>無料登録</a>
    <?php endif; ?>
  </div>
</section>

<?php
// ページ専用のCSS/JSをインラインで（面倒なら style.css に移してOK）
$this->start('css');
?>
<style>
.hero-wrap{min-height:72vh;display:flex;align-items:center}
.hero-bg-gradient{background:radial-gradient(1200px 600px at 20% 10%,#fff2bd 0%,rgba(255,242,189,0) 50%), radial-gradient(900px 500px at 85% 20%,#cfe8ff 0%,rgba(207,232,255,0) 60%),linear-gradient(180deg,#fff, #f7f9fc)}
.hero-wave{height:120px}
.text-gradient{background:linear-gradient(90deg,#eab308,#3b82f6);-webkit-background-clip:text;background-clip:text;color:transparent}
.glass{background:rgba(255,255,255,.75);backdrop-filter: blur(6px);border:1px solid rgba(0,0,0,.05)}
.icon-circle{width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%}
.icon-lg{width:56px;height:56px;display:inline-flex;align-items:center;justify-content:center;border-radius:14px;font-size:1.3rem}
.hero-boards{grid-template-columns:1fr}
@media (min-width: 768px){.hero-boards{grid-template-columns:1fr;max-width:480px;margin-left:auto}}
.brand{opacity:.8;filter:grayscale(100%);transition:.2s}
.brand:hover{opacity:1;filter:none}
.step-card{border:1px solid #eee;border-radius:14px;padding:24px;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,.04)}
.step-card .num{width:36px;height:36px;border-radius:50%;background:#000;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;margin-bottom:10px}
.feature-card{border:1px solid #eee;border-radius:16px;padding:24px;background:#fff;box-shadow:0 6px 20px rgba(0,0,0,.05);transition:transform .2s, box-shadow .2s}
.feature-card:hover{transform:translateY(-4px);box-shadow:0 12px 30px rgba(0,0,0,.08)}
.cta{border-radius:18px;background:linear-gradient(180deg,#fff,#fbfbff)}
/* アニメーション */
@keyframes rise{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.animate-rise{animation:rise .6s ease both}
.animate-rise.delay-1{animation-delay:.12s}
.animate-rise.delay-2{animation-delay:.24s}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
.animate-fade{animation:fadeIn .6s ease both}
.animate-fade.delay-1{animation-delay:.12s}
.animate-fade.delay-2{animation-delay:.24s}
</style>
<?php $this->end(); ?>

<?php $this->start('script'); ?>
<script>
// 交差監視で軽いフェード（スクロール表示）
(function(){
  const els = document.querySelectorAll('.animate-fade, .animate-rise');
  const io = new IntersectionObserver((entries)=>{
    entries.forEach(e=>{
      if(e.isIntersecting){ e.target.style.animationPlayState='running'; io.unobserve(e.target); }
    });
  }, {threshold: .12});
  els.forEach(el=>{
    el.style.animationPlayState='paused';
    io.observe(el);
  });
})();
</script>
<?php $this->end(); ?>
