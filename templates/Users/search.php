<div class="container mt-5">
    <h2>ユーザー検索</h2>

    <?= $this->Form->create(null, ['type' => 'get', 'url' => ['action' => 'search']]) ?>
        <?= $this->Form->control('q', [
            'label' => false,
            'placeholder' => 'ユーザー名で検索',
            'value' => $keyword,
            'class' => 'form-control mb-3'
        ]) ?>
        <?= $this->Form->button('検索', ['class' => 'btn btn-primary']) ?>
    <?= $this->Form->end() ?>

    <?php if (!empty($users)): ?>
        <ul class="list-group mt-4">
            <?php foreach ($users as $u): ?>
                <li class="list-group-item">
                    <?= $this->Html->link(h($u->name), ['action' => 'view', $u->id]) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php elseif ($this->request->getQuery('q')): ?>
        <p class="mt-4">ユーザーが見つかりませんでした。</p>
    <?php endif; ?>
</div>
