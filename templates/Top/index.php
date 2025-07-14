<form action="/portfolios/search" method="get" class="mb-4">
    <input type="text" name="q" class="form-control" placeholder="ポートフォリオを検索...">
</form>

<h1>おすすめポートフォリオ</h1>

<?php foreach ($portfolios as $p): ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5><?= h($p->title) ?> - <?= h($p->user->name ?? '匿名') ?></h5>
            <p><?= h($p->description) ?></p>
        </div>
    </div>
<?php endforeach; ?>
