<div class="hero-wrap position-relative overflow-hidden">
  <div class="hero-bg-gradient position-absolute top-0 start-0 w-100 h-100"></div>
  <svg class="hero-wave position-absolute bottom-0 start-0 w-100" viewBox="0 0 1440 220" preserveAspectRatio="none">
    <path fill="#ffffff" d="M0,192L48,186.7C96,181,192,171,288,149.3C384,128,480,96,576,85.3C672,75,768,85,864,101.3C960,117,1056,139,1152,138.7C1248,139,1344,117,1392,106.7L1440,96L1440,320L0,320Z"/>
  </svg>

  <div class="container position-relative" style="z-index:2;">
    <div class="row align-items-center py-5 py-lg-6">
      <div class="col-lg-6">
        <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis mb-3 px-3 py-2 shadow-sm">
          課題・レポート投稿OK / 学生・転職歓迎
        </span>
        <h1 class="display-5 fw-bold lh-sm text-dark mb-3">
          <span class="text-gradient">課題もレポートも作品も、</span><br>未来の実績に。
        </h1>
        <p class="lead text-secondary mb-4">
          授業の課題・レポート・演習から個人制作までOK。<br class="d-none d-md-inline">
          投稿 → 反応 → スカウトで、学びがキャリアにつながる。
        </p>

        <div class="d-flex flex-wrap gap-2">
          <?php if ($this->Identity->isLoggedIn()): ?>
            <a href="/portfolios/add" class="btn btn-primary btn-lg px-4">
              <i class="fa-solid fa-rocket-launch me-2"></i>課題 / 作品を投稿
            </a>
            <a href="/feed" class="btn btn-outline-secondary btn-lg px-4">
              <i class="fa-solid fa-waveform me-2"></i>タイムラインを見る
            </a>
            <a href="/users/search" class="btn btn-outline-secondary btn-lg px-4">
              <i class="fa-solid fa-users-viewfinder me-2"></i>つながる相手を探す
            </a>
          <?php else: ?>
            <a href="/users/register" class="btn btn-warning text-dark btn-lg px-4">
              <i class="fa-solid fa-user-plus me-2"></i>無料ではじめる
            </a>
            <a href="/users/login" class="btn btn-outline-dark btn-lg px-4">ログイン</a><br>
            <a href="/companies/add" class="btn btn-outline-dark btn-lg px-4">企業の方はこちら</a>
          <?php endif; ?>
        </div>

        <div class="d-flex align-items-center gap-3 mt-4 text-secondary small flex-wrap">
          <span><i class="fa-solid fa-envelope-circle-check me-1"></i>メール認証対応</span>
          <span><i class="fa-solid fa-user-lock me-1"></i>公開 / 非公開を選べる</span>
          <span><i class="fa-solid fa-heart me-1"></i>いいね・コメント・フォロー</span>
          <span><i class="fa-solid fa-paper-plane-top me-1"></i>DMで相談OK</span>
        </div>
      </div>

      <div class="col-lg-6 mt-5 mt-lg-0">
        <div class="hero-boards d-grid gap-3">
          <div class="card glass shadow-sm animate-rise">
            <div class="card-body p-4">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-circle bg-primary-subtle text-primary-emphasis me-3">
                  <i class="fa-regular fa-file-lines"></i>
                </div>
                <h5 class="mb-0 fw-semibold">提出物が“作品カード”に</h5>
              </div>
              <p class="text-secondary mb-0 small">
                スクショ・図表・リンク・一言メモでOK。<br>意図や学びが伝わるカードにまとまる。
              </p>
            </div>
          </div>
          <div class="card glass shadow-sm animate-rise delay-1">
            <div class="card-body p-4">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-circle bg-success-subtle text-success-emphasis me-3">
                  <i class="fa-solid fa-waveform-lines"></i>
                </div>
                <h5 class="mb-0 fw-semibold">タグとフォローで発見</h5>
              </div>
              <p class="text-secondary mb-0 small">
                #課題 #レポート #卒業制作 などで整理。<br>興味の近い人や企業に届きやすい。
              </p>
            </div>
          </div>
          <div class="card glass shadow-sm animate-rise delay-2">
            <div class="card-body p-4">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-circle bg-warning-subtle text-warning-emphasis me-3">
                  <i class="fa-solid fa-handshake-angle"></i>
                </div>
                <h5 class="mb-0 fw-semibold">反応がチャンスにつながる</h5>
              </div>
              <p class="text-secondary mb-0 small">
                いいね・コメント・DMで交流。<br>企業からの声かけや就活でも活用できる。
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- タグナビ -->
    <div class="row align-items-center g-3 mt-4">
      <div class="col-auto text-secondary small">いま話題のタグ</div>
      <div class="col">
        <div class="d-flex flex-wrap gap-2 align-items-center opacity-90">
          <?php
            $tags = ['#課題', '#レポート', '#卒業制作', '#個人開発', '#AI/ML', '#Web', '#UI/UX', '#電子工作', '#ロボティクス', '#研究'];
            foreach ($tags as $t):
          ?>
            <a class="chip" href="/search?tag=<?= urlencode(ltrim($t, '#')) ?>"><?= h($t) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 3ステップ -->
<section class="container py-5">
  <div class="text-center mb-5">
    <h2 class="fw-bold">はじめかたは、かんたん 3 ステップ</h2>
    <p class="text-secondary mb-0">登録 → 自己紹介カード → 課題 / 作品を投稿</p>
  </div>

  <div class="row g-4">
    <div class="col-md-4">
      <div class="step-card h-100 animate-fade">
        <div class="num">1</div>
        <h5 class="fw-semibold mb-2">無料登録</h5>
        <p class="text-secondary small mb-0">学生・社会人どちらもOK。メール認証ですぐ開始。</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="step-card h-100 animate-fade delay-1">
        <div class="num">2</div>
        <h5 class="fw-semibold mb-2">自己紹介カード</h5>
        <p class="text-secondary small mb-0">得意分野・興味タグ・リンクをセット。見つけてもらいやすく。</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="step-card h-100 animate-fade delay-2">
        <div class="num">3</div>
        <h5 class="fw-semibold mb-2">課題 / 作品を投稿</h5>
        <p class="text-secondary small mb-0">スクショ＋解説でOK。反応（いいね/コメント）から交流が始まる。</p>
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
          <h5 class="fw-semibold mt-3">課題・演習も歓迎</h5>
          <p class="text-secondary small mb-3">授業の提出物や研究ノートもOK。タグで整理して、伝わる形に。</p>
          <a href="/portfolios/add" class="link-underline link-underline-opacity-0">まずは投稿してみる →</a>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="feature-card h-100">
          <div class="icon-lg bg-success-subtle text-success-emphasis"><i class="fa-solid fa-people-arrows"></i></div>
          <h5 class="fw-semibold mt-3">SNSライクな交流</h5>
          <p class="text-secondary small mb-3">フォロー・コメント・DMでフラットにつながる。肩書きより「やったこと」。</p>
          <a href="/feed" class="link-underline link-underline-opacity-0">タイムラインをのぞく →</a>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="feature-card h-100">
          <div class="icon-lg bg-warning-subtle text-warning-emphasis"><i class="fa-solid fa-briefcase"></i></div>
          <h5 class="fw-semibold mt-3">就活・転職・スカウト</h5>
          <p class="text-secondary small mb-3">ポートフォリオURLで実力を可視化。企業アカウントからの相談も。</p>
          <a href="/users/search" class="link-underline link-underline-opacity-0">企業/仲間を探す →</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="container py-5">
  <div class="cta card border-0 shadow-sm p-4 p-md-5 text-center">
    <h3 class="fw-bold mb-3">今日の一歩が、明日の出会いに。</h3>
    <p class="text-secondary mb-4">作品はラフでもOK。まずは1枚のスクショと数行の解説から。</p>
    <?php if ($this->Identity->isLoggedIn()): ?>
      <a href="/portfolios/add" class="btn btn-primary btn-lg px-5"><i class="fa-solid fa-plus me-2"></i>投稿する</a>
    <?php else: ?>
      <a href="/users/register" class="btn btn-warning text-dark btn-lg px-5"><i class="fa-solid fa-user-plus me-2"></i>無料登録</a>
    <?php endif; ?>
  </div>
</section>

<?php $this->start('css'); ?>
<style>
.hero-wrap{min-height:72vh;display:flex;align-items:center}
.hero-bg-gradient{background:radial-gradient(1200px 600px at 20% 10%,#fff2bd 0%,rgba(255,242,189,0) 50%), radial-gradient(900px 500px at 85% 20%,#cfe8ff 0%,rgba(207,232,255,0) 60%),linear-gradient(180deg,#fff, #f7f9fc)}
.hero-wave{height:120px}
.text-gradient{background:linear-gradient(90deg,#eab308,#3b82f6);-webkit-background-clip:text;background-clip:text;color:transparent}
.glass{background:rgba(255,255,255,.75);backdrop-filter: blur(6px);border:1px solid rgba(0,0,0,.05)}
.icon-circle{width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%}
.icon-lg{width:56px;height:56px;display:inline-flex;align-items:center;justify-content:center;border-radius:14px;font-size:1.3rem}
.hero-boards{grid-template-columns:1fr}
@media (min-width: 768px){.hero-boards{grid-template-columns:1fr;max-width:520px;margin-left:auto}}
.brand{opacity:.8;filter:grayscale(100%);transition:.2s}
.brand:hover{opacity:1;filter:none}
.chip{display:inline-block;padding:.375rem .75rem;border-radius:999px;border:1px solid #e5e7eb;background:#fff;color:#374151;text-decoration:none;font-size:.9rem}
.chip:hover{background:#f9fafb}
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
