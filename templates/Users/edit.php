<?php
// templates/Users/edit.php
?>
<h2 class="mb-4">プロフィール編集</h2>

<?= $this->Form->create($user, ['type' => 'file']) ?>

<div class="mb-3">
    <?= $this->Form->control('name', ['label' => 'ユーザー名']) ?>
</div>

<div class="mb-3">
    <?= $this->Form->control('bio', [
        'label' => '自己紹介',
        'type' => 'textarea',
        'rows' => 4,
        'placeholder' => '自己紹介を入力してください'
    ]) ?>
</div>

<h4 class="mt-4">SNSリンク</h4>
<div class="mb-3">
    <?= $this->Form->control('twitter', ['label' => 'Twitterリンク']) ?>
    <?= $this->Form->control('github', ['label' => 'GitHubリンク']) ?>
    <?= $this->Form->control('youtube', ['label' => 'YouTubeリンク']) ?>
    <?= $this->Form->control('instagram', ['label' => 'Instagramリンク']) ?>
</div>

<h4 class="mt-4">アイコン画像</h4>
<div class="mb-3">
    <?php if (!empty($user->icon_path)): ?>
        <img src="<?= h($user->icon_path) ?>" class="rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;">
    <?php endif; ?>
    <?= $this->Form->control('icon', ['type' => 'file', 'label' => '画像を選択']) ?>
</div>

<?= $this->Form->button('更新', ['class' => 'btn btn-primary']) ?>
<?= $this->Form->end() ?>