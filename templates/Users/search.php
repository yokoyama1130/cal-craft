<?php
use Cake\Utility\Text;
?>
<div class="container mt-4">
    <h2 class="mb-4">ユーザー検索</h2>

    <?= $this->Form->create(null, ['type' => 'get', 'url' => ['action' => 'search'], 'class' => 'mb-4']) ?>
        <?= $this->Form->control('q', [
            'label' => false,
            'placeholder' => 'ユーザー名で検索',
            'value' => $keyword,
            'class' => 'form-control form-control-lg'
        ]) ?>
        <div class="mt-2">
            <?= $this->Form->button('検索', ['class' => 'btn btn-primary']) ?>
        </div>
    <?= $this->Form->end() ?>

    <?php if (!empty($users)): ?>
        <div class="row">
            <?php foreach ($users as $u): ?>
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex align-items-start">
                            <?php if (!empty($u->icon_url)): ?>
                                <img src="<?= h($u->icon_url) ?>" class="rounded-circle me-3" alt="User Icon" style="width: 48px; height: 48px; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user-circle fa-2x text-muted me-3"></i>
                            <?php endif; ?>
                            <div>
                                <h5 class="card-title mb-1">
                                    <?= $this->Html->link(h($u->name), ['action' => 'view', $u->id], ['class' => 'text-dark text-decoration-none']) ?>
                                </h5>
                                <?php if (!empty($u->bio)): ?>
                                    <p class="card-text text-muted small mb-0"><?= h(Text::truncate($u->bio, 60)) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($this->request->getQuery('q')): ?>
        <p class="mt-4 text-muted">ユーザーが見つかりませんでした。</p>
    <?php endif; ?>
</div>
