<div class="container mt-4">
    <!-- 検索フォーム -->
    <form action="/portfolios/search" method="get" class="mb-4">
        <input type="text" name="q" class="form-control form-control-lg"
            placeholder="検索..." value="<?= h($keyword ?? '') ?>">
    </form>

    <h2 class="mb-4">検索結果一覧</h2>

    <?php if (empty($portfolios)) : ?>
        <p class="text-muted">該当する投稿は見つかりませんでした。</p>
    <?php else : ?>
        <div class="row">
            <?php foreach ($portfolios as $p) : ?>
                <?php if ($p->is_public) : ?>
                    <div class="col-12 col-md-6 col-lg-4 mb-4">
                        <div class="youtube-card shadow-sm h-100">
                            <div class="youtube-thumb-wrapper">
                                <a
                                    href="<?= $this->Url->build([
                                        'controller' => 'Portfolios',
                                        'action' => 'view',
                                        $p->id,
                                    ]) ?>"
                                >
                                    <img src="<?= h($p->thumbnail) ?>" class="youtube-thumb" alt="thumbnail">
                                </a>
                            </div>
                          <div class="youtube-info">
                              <div class="d-flex justify-content-between align-items-start mb-1">
                              <?php if (!empty($p->user->icon_url)) : ?>
                                    <?php
                                        $iconUrl = h($p->user->icon_url ?? '');
                                        $altText = h(($p->user->name ?? 'user') . ' icon');
                                    ?>
                                    <img
                                        src="<?= $iconUrl ?>"
                                        alt="<?= $altText ?>"
                                        class="rounded-circle me-2"
                                        width="36"
                                        height="36"
                                        loading="lazy"
                                        decoding="async"
                                        style="object-fit: cover;"
                                    >
                              <?php else : ?>
                                  <i class="fas fa-user-circle fa-2x text-muted me-2"></i>
                              <?php endif; ?>
                                  <div class="title">
                                    <?= $this->Html->link(
                                        h($p->title),
                                        [
                                            'controller' => 'Portfolios',
                                            'action' => 'view',
                                            $p->id,
                                        ],
                                        [
                                            'class' => 'text-dark fw-bold text-decoration-none',
                                        ]
                                    ) ?>
                                  </div>
                                  <div class="like-section">
                                    <?= $this->Form->create(
                                        null,
                                        [
                                            'url' => [
                                            'controller' => 'Likes',
                                            'action' => 'add',
                                            ],
                                            'type' => 'post',
                                            'class' => 'd-inline',
                                        ]
                                    ) ?>
                                      <?= $this->Form->hidden('portfolio_id', ['value' => $p->id]) ?>
                                      <button class="btn p-0 like-button" data-portfolio-id="<?= h($p->id) ?>">
                                        <?php $iconClass = $p->liked_by_me ? 'fas liked' : 'far not-liked'; ?>
                                        <i class="fa-heart fa-lg <?= h($iconClass) ?>"></i>
                                          <span class="like-count small"><?= h($p->like_count) ?></span>
                                      </button>
                                      <?= $this->Form->end() ?>
                                  </div>
                              </div>
                          </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

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
</style>
