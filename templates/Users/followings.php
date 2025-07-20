<h2>フォロー一覧</h2>
<ul class="list-group">
    <?php foreach ($followings as $f): ?>
        <li class="list-group-item">
            <?= $this->Html->link(
                h($f->followed_user->name),
                ['controller' => 'Users', 'action' => 'view', $f->followed_user->id]
            ) ?>
        </li>
    <?php endforeach; ?>
</ul>