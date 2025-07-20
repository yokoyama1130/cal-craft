<h2>フォロワー一覧</h2>
<ul class="list-group">
    <?php foreach ($followers as $f): ?>
        <li class="list-group-item">
            <?= $this->Html->link(
                h($f->user->name),
                ['controller' => 'Users', 'action' => 'view', $f->user->id]
            ) ?>
        </li>
    <?php endforeach; ?>
</ul>
