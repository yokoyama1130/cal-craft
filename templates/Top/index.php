<div class="container mt-4">
    <form action="/portfolios/search" method="get" class="mb-4">
        <input type="text" name="q" class="form-control form-control-lg" placeholder="ポートフォリオを検索...">
    </form>

    <h2 class="mb-4">おすすめポートフォリオ</h2>

    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach ($portfolios as $p): ?>
            <div class="col">
                <div class="card h-100">
                    <?php if (!empty($p->thumbnail)): ?>
                        <img src="<?= h($p->thumbnail) ?>" class="card-img-top" alt="thumbnail">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= h($p->title) ?></h5>
                        <p class="card-text"><?= h($p->description) ?></p>
                    </div>
                    <div class="card-footer text-muted">
                        <?= h($p->user->name ?? '匿名ユーザー') ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
