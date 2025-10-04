<div class="container mt-5">

<!-- アイコン画像 -->
<?php if (!empty($user->icon_path)) : ?>
    <img src="/img/<?= h($user->icon_path) ?>"
      class="rounded-circle mb-2 shadow-sm border" 
      style="width: 100px; height: 100px; object-fit: cover;">
<?php endif; ?>

<h2><?= h($user->name) ?>さんのプロフィール</h2>

<!-- 自己紹介文 -->
<?php if (!empty($user->bio)) : ?>
    <p><strong>自己紹介:</strong><br><?= nl2br(h($user->bio)) ?></p>
<?php endif; ?>

<!-- フォロー／フォロワー -->
<div class="mb-3">
    <?= $this->Html->link("フォロー {$followingCount}人", ['action' => 'followings', $user->id]) ?>
    /
    <?= $this->Html->link("フォロワー {$followerCount}人", ['action' => 'followers', $user->id]) ?>
</div>

<!-- フォローボタン -->
<?php if ($this->request->getAttribute('identity')->get('id') !== $user->id) : ?>
    <?php if ($isFollowing) : ?>
        <?= $this->Form->postLink(
            'フォロー解除',
            [
                'controller' => 'Follows',
                'action' => 'unfollow',
                $user->id,
            ],
            [
                'class' => 'btn btn-outline-secondary',
            ]
        ) ?>
    <?php else : ?>
        <?= $this->Form->postLink(
            'フォロー',
            [
                'controller' => 'Follows',
                'action' => 'follow',
                $user->id,
            ],
            [
                'class' => 'btn btn-primary',
            ]
        ) ?>
    <?php endif; ?>
<?php endif; ?>

<!-- SNSリンク -->
<?php $sns = json_decode($user->sns_links ?? '[]', true); ?>

<div class="mb-3">
    <?php if (!empty($sns['twitter'])) : ?>
        <a href="<?= h($sns['twitter']) ?>" target="_blank">Twitter</a><br>
    <?php endif; ?>
    <?php if (!empty($sns['github'])) : ?>
        <a href="<?= h($sns['github']) ?>" target="_blank">GitHub</a><br>
    <?php endif; ?>
    <?php if (!empty($sns['youtube'])) : ?>
        <a href="<?= h($sns['youtube']) ?>" target="_blank">YouTube</a><br>
    <?php endif; ?>
    <?php if (!empty($sns['instagram'])) : ?>
        <a href="<?= h($sns['instagram']) ?>" target="_blank">Instagram</a><br>
    <?php endif; ?>
</div>

<?php if ($this->request->getAttribute('identity')->get('id') !== $user->id) : ?>
    <!-- フォローボタンなどのあとに追加 -->
    <?= $this->Html->link(
        'チャットを開始する 💬',
        [
            'controller' => 'Conversations',
            'action' => 'start',
            $user->id,
        ],
        [
            'class' => 'btn btn-outline-success mt-2',
        ]
    ) ?>
<?php endif; ?>

<!-- 投稿一覧 -->
<h2 class="mb-4">あなたの投稿一覧</h2>

<?php foreach ($portfolios as $p) : ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5><?= h($p->title) ?></h5>
            <p><?= h($p->description) ?></p>
            <p class="text-muted">公開状態：<?= $p->is_public ? '公開' : '非公開' ?></p>

            <?php if ($this->request->getAttribute('identity')->get('id') === $user->id) : ?>
                <div class="d-flex">
                    <a href="/portfolios/edit/<?= $p->id ?>" class="btn btn-outline-primary btn-sm me-2">編集</a>
                    <?= $this->Form->postLink(
                        '削除',
                        ['controller' => 'Portfolios', 'action' => 'delete', $p->id],
                        [
                            'class' => 'btn btn-outline-danger btn-sm',
                            'confirm' => '本当にこの投稿を削除しますか？',
                        ]
                    ) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

</div>
