<?php
use Cake\Utility\Text;
?>

<div class="container mt-4">
    <!-- 検索フォーム -->
    <form action="/portfolios/search" method="get" class="mb-4">
        <input type="text" name="q" class="form-control form-control-lg" placeholder="検索...">
    </form>

    <h2 class="mb-4">ホーム画面</h2>

    <!-- 投稿一覧 -->
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach ($portfolios as $p): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <?php if (!empty($p->thumbnail)): ?>
                        <img src="<?= h($p->thumbnail) ?>" class="card-img-top" alt="thumbnail" style="object-fit: cover; height: 200px;">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title">
                            <?= $this->Html->link(h($p->title), ['controller' => 'Portfolios', 'action' => 'view', $p->id], ['class' => 'text-decoration-none text-dark']) ?>
                        </h5>
                        <p class="card-text"><?= h(Text::truncate($p->description, 100)) ?></p>
                    </div>
                    <div class="card-footer text-muted small">
                        <?= h($p->user->name ?? '匿名ユーザー') ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
