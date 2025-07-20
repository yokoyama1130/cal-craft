<h2 class="mb-4">お気に入りした投稿</h2>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?= $this->Html->css('style') ?>
<?php if (empty($portfolios)): ?>
    <p>お気に入りした投稿はまだありません。</p>
<?php else: ?>

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
                    <p class="card-text"><?= h(\Cake\Utility\Text::truncate($p->description, 100)) ?></p>
                </div>

                <div class="card-footer text-center">
                    <?= $this->Form->create(null, ['url' => ['controller' => 'Likes', 'action' => 'toggle'], 'type' => 'post', 'class' => 'd-inline']) ?>
                    <?= $this->Form->hidden('portfolio_id', ['value' => $p->id]) ?>

                    <button class="btn border-0 bg-white like-button" data-portfolio-id="<?= h($p->id) ?>">
                        <i class="fa-heart fa-2x <?= $p->liked_by_me ? 'fas liked' : 'far not-liked' ?>"></i>
                    </button>

                    <div class="small text-muted mt-1 like-count">
                        👍 <?= h($p->like_count) ?>件のいいね
                    </div>

                    <?= $this->Form->end() ?>
                </div>

            </div>
        </div>
<?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- 必要な JS（likeの切り替え） -->
<script>
document.querySelectorAll('.like-button').forEach(button => {
    button.addEventListener('click', function (e) {
        e.preventDefault();
        const portfolioId = this.dataset.portfolioId;
        const icon = this.querySelector('i');
        const countElem = this.parentElement.querySelector('.like-count');

        fetch('/likes/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': document.querySelector('meta[name="csrfToken"]').content
            },
            body: `portfolio_id=${portfolioId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.liked) {
                    icon.classList.remove('far', 'not-liked');
                    icon.classList.add('fas', 'liked');
                } else {
                    icon.classList.remove('fas', 'liked');
                    icon.classList.add('far', 'not-liked');
                }
                countElem.textContent = `👍 ${data.likeCount}件のいいね`;
            }
        });
    });
});
</script>
