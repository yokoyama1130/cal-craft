<?php
use Cake\Utility\Text;
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<div class="container mt-5">

<!-- プロフィール情報 -->
<div class="d-flex align-items-center mb-4">
  <?php if (!empty($user->icon_path)): ?>
    <img src="/img/<?= h($user->icon_path) ?>" class="rounded-circle me-3 shadow-sm border" style="width: 100px; height: 100px; object-fit: cover;">
  <?php endif; ?>
  <div>
    <h2 class="mb-1"><?= h($user->name) ?>さんのプロフィール</h2>
    <div>
      <?= $this->Html->link("フォロー {$followingCount}人", ['action' => 'followings', $user->id]) ?> /
      <?= $this->Html->link("フォロワー {$followerCount}人", ['action' => 'followers', $user->id]) ?>
    </div>
    <!-- フォローボタン -->
    <?php if ($this->request->getAttribute('identity')->get('id') !== $user->id): ?>
    <div class="mt-2 d-flex gap-2">
      <?php if ($isFollowing): ?>
        <?= $this->Form->postLink('フォロー解除', ['controller' => 'Follows', 'action' => 'unfollow', $user->id], ['class' => 'btn btn-outline-secondary']) ?>
      <?php else: ?>
        <?= $this->Form->postLink('フォロー', ['controller' => 'Follows', 'action' => 'follow', $user->id], ['class' => 'btn btn-outline-primary']) ?>
      <?php endif; ?>

      <!-- ここが追加：メッセージボタン -->
      <?php if ($this->Identity->isLoggedIn()): ?>
        <a href="<?= $this->Url->build(['controller' => 'Conversations', 'action' => 'start', $user->id]) ?>"
          class="btn btn-primary">
          <i class="fa-regular fa-paper-plane me-1"></i> メッセージ
        </a>
      <?php else: ?>
        <a href="/users/login?redirect=<?= urlencode($this->request->getRequestTarget()) ?>" class="btn btn-primary">
          <i class="fa-regular fa-paper-plane me-1"></i> メッセージ
        </a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  </div>
</div>

<?php if ($this->request->getAttribute('identity')->get('id') === $user->id): ?>
  <div class="mb-3">
    <?= $this->Html->link('プロフィールを編集', ['controller' => 'Users', 'action' => 'edit'], ['class' => 'btn btn-outline-primary']) ?>
  </div>
<?php endif; ?>

<!-- 自己紹介文 -->
<?php if (!empty($user->bio)): ?>
  <p><strong>自己紹介:</strong><br><?= nl2br(h($user->bio)) ?></p>
<?php endif; ?>

<!-- SNSリンク -->
<?php $sns = json_decode($user->sns_links ?? '[]', true); ?>
<div class="mb-3">
  <?php if (!empty($sns['twitter'])): ?>
    <a href="<?= h($sns['twitter']) ?>" target="_blank">Twitter</a><br>
  <?php endif; ?>
  <?php if (!empty($sns['github'])): ?>
    <a href="<?= h($sns['github']) ?>" target="_blank">GitHub</a><br>
  <?php endif; ?>
  <?php if (!empty($sns['youtube'])): ?>
    <a href="<?= h($sns['youtube']) ?>" target="_blank">YouTube</a><br>
  <?php endif; ?>
  <?php if (!empty($sns['instagram'])): ?>
    <a href="<?= h($sns['instagram']) ?>" target="_blank">Instagram</a><br>
  <?php endif; ?>
</div>

<!-- 投稿一覧 -->
<h2 class="mb-4"><?= h($user->name) ?>さんの投稿一覧</h2>
<div class="row">
  <?php foreach ($portfolios as $p): ?>
    <div class="col-12 col-md-6 col-lg-4 mb-4">
      <div class="youtube-card shadow-sm">
        <div class="youtube-thumb-wrapper">
          <?php if (!empty($p->thumbnail)): ?>
            <a href="<?= $this->Url->build(['controller' => 'Portfolios', 'action' => 'view', $p->id]) ?>">
              <img src="<?= h($p->thumbnail) ?>" class="youtube-thumb" alt="thumbnail">
            </a>
          <?php endif; ?>
        </div>
        <div class="youtube-info">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <div class="title">
              <?= $this->Html->link(h($p->title), ['controller' => 'Portfolios', 'action' => 'view', $p->id], ['class' => 'text-dark fw-bold text-decoration-none']) ?>
            </div>
            <?php if ($this->request->getAttribute('identity')->get('id') === $user->id): ?>
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                  操作
                </button>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="/portfolios/edit/<?= $p->id ?>">編集</a></li>
                  <li><?= $this->Form->postLink('削除', ['controller' => 'Portfolios', 'action' => 'delete', $p->id], ['class' => 'dropdown-item', 'confirm' => '本当に削除しますか？']) ?></li>
                </ul>
              </div>
            <?php endif; ?>
          </div>
          <p class="text-muted small">公開状態：<?= $p->is_public ? '公開' : '非公開' ?></p>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<style>
.youtube-card {
  border-radius: 12px;
  overflow: hidden;
  background-color: #fff;
  transition: box-shadow 0.3s;
  border: 1px solid #ddd;
}
.youtube-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.youtube-thumb-wrapper {
  width: 100%;
  height: 180px;
  overflow: hidden;
}
.youtube-thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.youtube-info {
  padding: 12px;
}
.youtube-info .title {
  font-size: 1rem;
  font-weight: 600;
  flex: 1;
}
</style>

<script>
document.querySelectorAll('.dropdown-toggle').forEach(button => {
  button.addEventListener('click', function (e) {
    e.stopPropagation();
  });
});
</script>
</div>
