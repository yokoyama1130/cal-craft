<h2 class="mb-4 fw-bold">通知一覧</h2>

<?php if (empty($notifications)): ?>
    <div class="alert alert-secondary">通知はまだありません。</div>
<?php else: ?>
    <div class="list-group">
        <?php foreach ($notifications as $n): ?>
            <?php
                $sender = $n->sender_user ?? null;
                $senderName = $sender ? h($sender->name) : '不明なユーザー';
                $message = '';
                $link = null;

                switch ($n->type) {
                    case 'like':
                        $message = "{$senderName} さんがあなたの投稿にいいねしました。";
                        $link = ['controller' => 'Portfolios', 'action' => 'view', $n->portfolio_id];
                        break;
                    case 'comment':
                        $message = "{$senderName} さんがあなたの投稿にコメントしました。";
                        $link = ['controller' => 'Portfolios', 'action' => 'view', $n->portfolio_id];
                        break;
                    case 'follow':
                        $message = "{$senderName} さんがあなたをフォローしました。";
                        $link = ['controller' => 'Users', 'action' => 'profile', $sender->id];
                        break;
                    default:
                        $message = "{$senderName} さんから通知があります。";
                        break;
                }
            ?>

            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-start <?= $n->is_read ? '' : 'bg-light' ?>">
                <div class="ms-2 me-auto">
                    <?php if (!empty($n->portfolio_id)): ?>
                        <?= $this->Html->link($message, ['controller' => 'Portfolios', 'action' => 'view', $n->portfolio_id], ['class' => 'text-decoration-none fw-semibold text-dark']) ?>
                    <?php else: ?>
                        <span class="fw-semibold"><?= h($message) ?></span>
                    <?php endif; ?>

                    <div class="small text-muted mt-1">
                        <?= ($n->created !== null) ? $n->created->nice() : '' ?>
                    </div>
                </div>

                <?php if (!$n->is_read): ?>
                    <span class="badge bg-danger align-self-center">NEW</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
