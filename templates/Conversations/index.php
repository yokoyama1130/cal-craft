<h2 class="mb-4">メッセージ一覧</h2>

<?php if (empty($conversations)): ?>
  <div class="alert alert-info">まだメッセージはありません。</div>
<?php else: ?>
  <div class="list-group">
    <?php foreach ($conversations as $c): ?>
      <?php
        $partner = ($c->user1_id === $userId) ? $c->user2 : $c->user1;
      ?>
      <a href="<?= $this->Url->build(['action' => 'view', $c->id]) ?>" class="list-group-item list-group-item-action d-flex align-items-center py-3 shadow-sm mb-2 rounded text-dark text-decoration-none">
        <!-- 相手のアイコン -->
        <?php if (!empty($partner->icon_path)): ?>
          <img src="/img/<?= h($partner->icon_path) ?>" alt="icon" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
        <?php else: ?>
          <i class="fas fa-user-circle fa-2x text-muted me-3"></i>
        <?php endif; ?>

        <!-- 相手の名前と案内 -->
        <div>
          <div class="fw-bold"><?= h($partner->name) ?> さん</div>
          <div class="small text-muted">会話を表示</div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
