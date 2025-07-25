<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($portfolio->title) ?> | Calcraft</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter&display=swap" rel="stylesheet">
  <style>
    body {
      background-color: #fdfdfd;
      font-family: 'Inter', sans-serif;
      color: #333;
    }
    h1, h2, h3, h4, h5 {
      font-family: 'Playfair Display', serif;
    }
    .portfolio-header {
      background: linear-gradient(90deg, #4a5568, #2d3748);
      color: white;
      padding: 2rem;
      border-radius: 0.5rem;
      margin-bottom: 2rem;
      position: relative;
    }
    .portfolio-img {
      max-width: 100%;
      border-radius: 0.5rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .section-title {
      margin-top: 2rem;
      margin-bottom: 1rem;
      border-left: 5px solid #4a5568;
      padding-left: 0.5rem;
      font-weight: bold;
    }
    .card-comment {
      border: none;
      border-left: 4px solid #4a5568;
      border-radius: 0.5rem;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      padding: 1rem;
      margin-bottom: 1rem;
      background-color: #fff;
    }
    .comment-form textarea {
      border-radius: 0.5rem;
      resize: vertical;
    }
    .fade-in {
      animation: fadeIn 0.7s ease-in;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
<div class="d-flex align-items-center mb-4 fade-in">
  <?php if (!empty($portfolio->user->icon_path)): ?>
    <img src="/img/<?= h($portfolio->user->icon_path) ?>" class="rounded-circle me-3 shadow-sm border" style="width: 100px; height: 100px; object-fit: cover;">
  <?php else: ?>
    <i class="fas fa-user-circle fa-5x text-muted me-3"></i>
  <?php endif; ?>
  <div>
    <h2 class="mb-1"><?= h($portfolio->user->name) ?></h2>
    <div>
      <?= $this->Html->link(
        'フォロー <span id="following-count">' . h($followingCount) . '</span>人',
        ['controller' => 'Users', 'action' => 'followings', $portfolio->user->id],
        ['escape' => false]
      ) ?>
      /
      <?= $this->Html->link(
        'フォロワー <span id="follower-count">' . h($followerCount) . '</span>人',
        ['controller' => 'Users', 'action' => 'followers', $portfolio->user->id],
        ['escape' => false]
      ) ?>
    </div>
    <div id="follow-button-container">
      <?php if ($this->request->getAttribute('identity')->get('id') !== $portfolio->user->id): ?>
        <button
          class="btn <?= $isFollowing ? 'btn-outline-secondary' : 'btn-primary' ?>"
          id="follow-button"
          data-following="<?= $isFollowing ? '1' : '0' ?>"
          data-user-id="<?= h($portfolio->user->id) ?>"
        >
          <?= $isFollowing ? 'フォロー解除' : 'フォロー' ?>
        </button>
      <?php endif; ?>
    </div>
  </div>
</div>
  <div class="portfolio-header text-center fade-in d-flex align-items-center justify-content-center gap-3">
    <div class="d-flex align-items-center justify-content-center mb-2">
      <h1 class="m-0"><?= h($portfolio->title) ?></h1>
    </div>
  </div>
  <?php if ($portfolio->thumbnail): ?>
    <div class="text-center mb-4 fade-in">
      <img src="<?= h($portfolio->thumbnail) ?>" alt="Thumbnail" class="portfolio-img">
    </div>
  <?php endif; ?>

  <div class="fade-in">
    <h4 class="section-title">概要</h4>
    <p><?= nl2br(h($portfolio->description)) ?></p>

    <?php if ($portfolio->link): ?>
      <p><strong>🔗 関連リンク:</strong> <a href="<?= h($portfolio->link) ?>" target="_blank"><?= h($portfolio->link) ?></a></p>
    <?php endif; ?>
  </div>

  <?php if (!empty($portfolio->category) && $portfolio->category->slug === 'mechanical'): ?>
    <div class="fade-in">
      <h4 class="section-title">🔧 機械系ポートフォリオ詳細</h4>

      <?php if ($portfolio->purpose || $portfolio->basic_spec): ?>
        <h5>[1] 設計構想・目的</h5>
        <?php if ($portfolio->purpose): ?><p><strong>目的:</strong> <?= nl2br(h($portfolio->purpose)) ?></p><?php endif; ?>
        <?php if ($portfolio->basic_spec): ?><p><strong>基本仕様:</strong> <?= nl2br(h($portfolio->basic_spec)) ?></p><?php endif; ?>
      <?php endif; ?>

      <?php if ($portfolio->design_url || $portfolio->design_description || $portfolio->parts_list): ?>
        <h5 class="mt-4">[2] 設計と部品情報</h5>
        <?php if ($portfolio->design_url): ?><p><strong>設計書:</strong> <a href="<?= h($portfolio->design_url) ?>" target="_blank"><?= h($portfolio->design_url) ?></a></p><?php endif; ?>
        <?php if ($portfolio->design_description): ?><p><strong>説明:</strong> <?= nl2br(h($portfolio->design_description)) ?></p><?php endif; ?>
        <?php if ($portfolio->parts_list): ?><pre><?= h($portfolio->parts_list) ?></pre><?php endif; ?>
      <?php endif; ?>

      <?php if ($portfolio->processing_method || $portfolio->processing_notes || $portfolio->analysis_method || $portfolio->analysis_result): ?>
        <h5 class="mt-4">[3] 加工・解析</h5>
        <?php if ($portfolio->processing_method): ?><p><strong>加工方法:</strong> <?= nl2br(h($portfolio->processing_method)) ?></p><?php endif; ?>
        <?php if ($portfolio->processing_notes): ?><p><strong>ノウハウ:</strong> <?= nl2br(h($portfolio->processing_notes)) ?></p><?php endif; ?>
        <?php if ($portfolio->analysis_method): ?><p><strong>解析:</strong> <?= nl2br(h($portfolio->analysis_method)) ?></p><?php endif; ?>
        <?php if ($portfolio->analysis_result): ?><p><strong>考察:</strong> <?= nl2br(h($portfolio->analysis_result)) ?></p><?php endif; ?>
      <?php endif; ?>

      <?php if ($portfolio->development_period || $portfolio->mechanical_notes || $portfolio->reference_links): ?>
        <h5 class="mt-4">[4] 補足</h5>
        <?php if ($portfolio->development_period): ?><p><strong>期間:</strong> <?= h($portfolio->development_period) ?></p><?php endif; ?>
        <?php if ($portfolio->mechanical_notes): ?><p><strong>反省・工夫:</strong> <?= nl2br(h($portfolio->mechanical_notes)) ?></p><?php endif; ?>
        <?php if ($portfolio->reference_links): ?><pre><?= h($portfolio->reference_links) ?></pre><?php endif; ?>
      <?php endif; ?>
      <?php if ($portfolio->tool_used): ?>
        <p><strong>使用ツール:</strong> <?= nl2br(h($portfolio->tool_used)) ?></p>
      <?php endif; ?>

      <?php if ($portfolio->material_used): ?>
        <p><strong>使用材料:</strong> <?= nl2br(h($portfolio->material_used)) ?></p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="fade-in mt-5">
    <h3 class="section-title">💬 コメント</h3>

    <?php if (!empty($comments)): ?>
      <?php foreach ($comments as $comment): ?>
        <div class="card-comment">
          <strong><?= h($comment->user->name) ?></strong>
          <p class="mb-1"><?= nl2br(h($comment->content)) ?></p>
          <small class="text-muted"><?= $comment->created->nice() ?></small>
          <?php if ($comment->user_id === $this->request->getAttribute('identity')->get('id')): ?>
            <div class="mt-2">
              <?= $this->Html->link('編集', ['controller' => 'Comments', 'action' => 'edit', $comment->id], ['class' => 'btn btn-sm btn-outline-secondary me-2']) ?>
              <?= $this->Form->postLink('削除', ['controller' => 'Comments', 'action' => 'delete', $comment->id], [
                  'confirm' => '本当に削除しますか？',
                  'class' => 'btn btn-sm btn-outline-danger'
              ]) ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-muted">コメントはまだありません。</p>
    <?php endif; ?>

    <!-- 💬 コメントフォーム -->
    <div class="card p-4 comment-form mt-5">
    <?= $this->Form->create(null, ['url' => ['controller' => 'Comments', 'action' => 'add']]) ?>
    <?= $this->Form->hidden('portfolio_id', ['value' => $portfolio->id]) ?>
    <?= $this->Form->control('content', [
      'label' => false,
      'rows' => 4,
      'placeholder' => '感想やフィードバックを書いてみましょう ✍️',
      'class' => 'form-control mb-3'
    ]) ?>
    <?= $this->Form->button('💬 コメント投稿', ['class' => 'btn btn-primary']) ?>
    <?= $this->Form->end() ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const button = document.getElementById('follow-button');
  if (!button) return;

  button.addEventListener('click', function () {
    const isFollowing = button.dataset.following === '1';
    const userId = button.dataset.userId;
    const url = isFollowing ? `/follows/unfollow-ajax/${userId}` : `/follows/follow-ajax/${userId}`;

    fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-Token': '<?= $this->request->getAttribute("csrfToken") ?>',
        'Accept': 'application/json'
      }
    })
    .then(res => res.json())
    .then(data => {
      const followerCountSpan = document.getElementById('follower-count');

      if (data.status === 'followed') {
        button.textContent = 'フォロー解除';
        button.className = 'btn btn-outline-secondary';
        button.dataset.following = '1';

        // フォロワー数＋1
        if (followerCountSpan) {
          let count = parseInt(followerCountSpan.textContent);
          followerCountSpan.textContent = count + 1;
        }

      } else if (data.status === 'unfollowed') {
        button.textContent = 'フォロー';
        button.className = 'btn btn-primary';
        button.dataset.following = '0';

        // フォロワー数−1
        if (followerCountSpan) {
          let count = parseInt(followerCountSpan.textContent);
          followerCountSpan.textContent = count - 1;
        }
      }
    })
    .catch(err => console.error('フォロー切り替えエラー:', err));
  });
});
</script>


