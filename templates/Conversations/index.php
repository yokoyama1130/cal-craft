<h2 class="mb-4">メッセージ一覧</h2>

<?php if (empty($conversations)) : ?>
  <div class="alert alert-info">まだメッセージはありません。</div>
<?php else : ?>
  <div class="list-group">
    <?php foreach ($conversations as $c) : ?>
        <?php
          // コントローラ側で $c->partner_type ('user'|'company') と $c->partner をセット済み
          $partner = $c->partner ?? null;
          $partnerName = $partner->name ?? '相手';
          // 画像パス（user: icon_path / company: logo_path）
          $rawIcon = null;
        if (!empty($partner)) {
            if (($c->partner_type ?? '') === 'company') {
                $rawIcon = $partner->logo_path ?? null;
            } else {
                $rawIcon = $partner->icon_path ?? null;
            }
        }
          // URL整形（/ から始まってなければ /img/ プレフィックス）
          $iconUrl = null;
        if (!empty($rawIcon)) {
            $iconUrl = str_starts_with($rawIcon, '/') ? $rawIcon : '/img/' . ltrim($rawIcon, '/');
        }
        ?>
      <a href="<?= $this->Url->build(['action' => 'view', $c->id]) ?>"
         class="list-group-item list-group-item-action d-flex align-items-center py-3 shadow-sm mb-2 rounded text-dark text-decoration-none">
        <?php if ($iconUrl) : ?>
          <img src="<?= h($iconUrl) ?>" alt="icon" class="rounded-circle me-3"
               style="width:50px;height:50px;object-fit:cover;">
        <?php else : ?>
          <i class="fas fa-user-circle fa-2x text-muted me-3"></i>
        <?php endif; ?>

        <div>
          <div class="fw-bold"><?= h($partnerName) ?> さん</div>
          <div class="small text-muted">
            <?= $c->partner_type === 'company' ? '企業との会話を表示' : '会話を表示' ?>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
