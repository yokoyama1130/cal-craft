<form action="/portfolios/search" method="get" class="mb-4">
    <input type="text" name="q" class="form-control form-control-lg"
        placeholder="検索..."
        value="<?= h($keyword ?? '') ?>">
</form>
<h2 class="mb-4">検索結果一覧</h2>
<?php if (empty($portfolios)): ?>
    <p>該当する投稿は見つかりませんでした。</p>
<?php endif; ?>
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
  <?php foreach ($portfolios as $portfolio): ?>
    <?php if ($portfolio->is_public): ?>
      <div class="col">
        <div class="card h-100 shadow-sm">
          <img src="<?= h($portfolio->thumbnail) ?>" class="card-img-top" alt="<?= h($portfolio->title) ?>">
          <div class="card-body">
            <h5 class="card-title"><?= h($portfolio->title) ?></h5>
            <p class="card-text"><?= h($portfolio->description) ?></p>
          </div>
          <div class="card-footer d-flex justify-content-between">
            <a href="/portfolios/view/<?= $portfolio->id ?>" class="btn btn-outline-primary btn-sm">詳細</a>
            <?php if ($this->Identity->get('id') === $portfolio->user_id): ?>
              <div>
                <a href="/portfolios/edit/<?= $portfolio->id ?>" class="btn btn-outline-secondary btn-sm">編集</a>
                <?= $this->Form->postLink('削除', ['action' => 'delete', $portfolio->id], [
                  'confirm' => '削除してよろしいですか？',
                  'class' => 'btn btn-outline-danger btn-sm'
                ]) ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>
</div>
