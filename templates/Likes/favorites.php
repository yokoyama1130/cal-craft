<h2 class="mb-4">お気に入りした投稿</h2>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<?php if (empty($portfolios)) : ?>
    <p>お気に入りした投稿はまだありません。</p>
<?php else : ?>
    <div class="row">
        <?php foreach ($portfolios as $p) : ?>
            <div class="col-12 col-md-6 col-lg-4 mb-4">
                <div class="youtube-card shadow-sm">
                    <div class="youtube-thumb-wrapper">
                        <?php if (!empty($p->thumbnail)) : ?>
                            <a href="<?= $this->Url->build(['controller' => 'Portfolios', 'action' => 'view', $p->id]) ?>">
                                <img src="<?= h($p->thumbnail) ?>" class="youtube-thumb" alt="thumbnail">
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="youtube-info">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <!-- ユーザーアイコン -->
                            <?php if (!empty($p->user->icon_url)) : ?>
                                <img src="<?= h($p->user->icon_url) ?>" alt="user icon" class="rounded-circle me-2" style="width: 36px; height: 36px; object-fit: cover;">
                            <?php else : ?>
                                <i class="fas fa-user-circle fa-2x text-muted me-2"></i>
                            <?php endif; ?>

                            <!-- タイトル -->
                            <div class="title">
                                <?= $this->Html->link(h($p->title), ['controller' => 'Portfolios', 'action' => 'view', $p->id], ['class' => 'text-dark fw-bold text-decoration-none']) ?>
                            </div>

                            <!-- いいねボタン -->
                            <div class="like-section">
                                <?= $this->Form->create(null, ['url' => ['controller' => 'Likes', 'action' => 'toggle'], 'type' => 'post', 'class' => 'd-inline']) ?>
                                <?= $this->Form->hidden('portfolio_id', ['value' => $p->id]) ?>
                                <button class="btn p-0 like-button" data-portfolio-id="<?= h($p->id) ?>">
                                    <i class="fa-heart fa-lg <?= $p->liked_by_me ? 'fas liked' : 'far not-liked' ?>"></i>
                                    <span class="like-count small"><?= h($p->like_count) ?></span>
                                </button>
                                <?= $this->Form->end() ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.youtube-card {
    border-radius: 12px;
    overflow: hidden;
    background-color: #fff;
    transition: box-shadow 0.3s;
    border: 1px solid #ddd;
}
.youtube-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.youtube-thumb-wrapper {
    width: 100%;
    height: 180px;
    overflow: hidden;
}
.youtube-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.youtube-info {
    padding: 12px;
}
.youtube-info .title {
    font-size: 1rem;
    font-weight: 600;
    flex: 1;
}
.like-button {
    border: none;
    background: none;
}
.like-button i {
    transition: color 0.3s ease;
}
i.fa-heart.liked {
    color: hotpink !important;
}
i.fa-heart.not-liked {
    color: #ccc !important;
}
</style>

<script>
document.querySelectorAll('.like-button').forEach(button => {
    button.addEventListener('click', function (e) {
        e.preventDefault();
        const portfolioId = this.dataset.portfolioId;
        const icon = this.querySelector('i');
        const countElem = this.querySelector('.like-count');

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
                countElem.textContent = data.likeCount;
            }
        });
    });
});
</script>
