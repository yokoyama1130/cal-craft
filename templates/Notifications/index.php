<h2 class="mb-4">通知一覧</h2>

<?php if (empty($notifications)): ?>
    <p>通知はまだありません。</p>
<?php else: ?>
    <ul class="list-group">
        <?php foreach ($notifications as $n): ?>
            <li class="list-group-item <?= $n->is_read ? '' : 'fw-bold' ?>">
                <?= h($n->message) ?>
                <span class="text-muted float-end small"><?= $n->created->format('Y-m-d H:i') ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
