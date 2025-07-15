<h2 class="mb-4">通知一覧</h2>

<ul class="list-group">
    <?php if (empty($notifications)): ?>
        <li class="list-group-item text-muted">通知はまだありません。</li>
    <?php endif; ?>

    <?php foreach ($notifications as $n): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center <?= $n->is_read ? '' : 'fw-bold' ?>">
            <?php if ($n->portfolio_id): ?>
                <a href="<?= $this->Url->build(['controller' => 'Portfolios', 'action' => 'view', $n->portfolio_id]) ?>" class="text-decoration-none text-dark">
                    <?= h($n->type === 'like' ? 'あなたの投稿にいいねがありました' : '通知') ?>
                </a>
            <?php else: ?>
                <?= h($n->type === 'like' ? 'あなたの投稿にいいねがありました' : '通知') ?>
            <?php endif; ?>
            <small class="text-muted"><?= $n->created->format('Y/m/d H:i') ?></small>
        </li>
    <?php endforeach; ?>
</ul>
