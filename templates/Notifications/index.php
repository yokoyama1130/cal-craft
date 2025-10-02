<h2 class="mb-4 fw-bold">通知一覧</h2>

<?php if (empty($notifications)) : ?>
    <div class="alert alert-info text-center py-4 shadow-sm">
        <i class="fas fa-bell-slash fa-2x text-secondary mb-2"></i><br>
        通知はまだありません。
    </div>
<?php else : ?>
    <div class="list-group">
        <?php foreach ($notifications as $n) : ?>
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
                    if ($sender) {
                        $link = ['controller' => 'Users', 'action' => 'profile', $sender->id];
                    }
                    break;

                default:
                    $message = "{$senderName} さんから通知があります。";
                    break;
            }
            ?>

            <div class="list-group-item d-flex justify-content-between align-items-start p-3 rounded shadow-sm mb-2 <?= $n->is_read ? 'bg-white' : 'bg-light' ?>">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-user-circle fa-2x text-secondary"></i>
                    </div>
                    <div>
                        <?php if (!empty($link)) : ?>
                            <?= $this->Html->link($message, $link, ['class' => 'text-dark text-decoration-none fw-semibold']) ?>
                        <?php else : ?>
                            <span class="fw-semibold"><?= h($message) ?></span>
                        <?php endif; ?>

                        <div class="small text-muted mt-1">
                            <?= $n->created !== null ? $n->created->nice() : '' ?>
                        </div>
                    </div>
                </div>

                <?php if (!$n->is_read) : ?>
                    <span class="badge bg-danger align-self-center ms-3">NEW</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
