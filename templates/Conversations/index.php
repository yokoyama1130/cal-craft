<!-- templates/Conversations/index.php -->

<h2 class="mb-4">メッセージ一覧</h2>

<?php if (empty($conversations)): ?>
    <p>メッセージはまだありません。</p>
<?php else: ?>
    <ul class="list-group">
        <?php foreach ($conversations as $c): ?>
            <li class="list-group-item">
                <?= $this->Html->link(
                    h(($c->user1_id === $userId) ? $c->user2->name : $c->user1->name) . ' さんとの会話',
                    ['action' => 'view', $c->id],
                    ['class' => 'text-decoration-none']
                ) ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
