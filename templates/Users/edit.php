<?php
// templates/Users/edit.php
?>

<div class="container mt-5" style="max-width: 80%;">
  <h2 class="mb-4 text-center">プロフィールを編集</h2>

  <div class="card shadow-sm p-4">
    <?= $this->Form->create($user, ['type' => 'file']) ?>

    <!-- ユーザー名 -->
    <div class="mb-3">
      <?= $this->Form->control('name', [
        'label' => 'ユーザー名',
        'class' => 'form-control'
      ]) ?>
    </div>

    <!-- 自己紹介 -->
    <div class="mb-3">
      <?= $this->Form->control('bio', [
        'label' => '自己紹介',
        'type' => 'textarea',
        'rows' => 4,
        'placeholder' => '自己紹介を入力してください',
        'class' => 'form-control'
      ]) ?>
    </div>

    <!-- SNSリンク -->
    <h5 class="mt-4 mb-2">🌐 SNSリンク</h5>
    <div class="row">
      <div class="col-md-6 mb-3">
        <?= $this->Form->control('twitter', ['label' => 'Twitter', 'class' => 'form-control', 'placeholder' => 'https://twitter.com/...']) ?>
      </div>
      <div class="col-md-6 mb-3">
        <?= $this->Form->control('github', ['label' => 'GitHub', 'class' => 'form-control', 'placeholder' => 'https://github.com/...']) ?>
      </div>
      <div class="col-md-6 mb-3">
        <?= $this->Form->control('youtube', ['label' => 'YouTube', 'class' => 'form-control', 'placeholder' => 'https://youtube.com/...']) ?>
      </div>
      <div class="col-md-6 mb-3">
        <?= $this->Form->control('instagram', ['label' => 'Instagram', 'class' => 'form-control', 'placeholder' => 'https://instagram.com/...']) ?>
      </div>
    </div>

    <!-- アイコン画像 -->
    <h5 class="mt-4 mb-2">🖼️ プロフィール画像</h5>
    <div class="mb-3 text-center">
      <?php if (!empty($user->icon_path)): ?>
        <img src="<?= h($user->icon_path) ?>" class="rounded-circle shadow-sm mb-3 border" style="width: 120px; height: 120px; object-fit: cover;">
      <?php endif; ?>
      <?= $this->Form->control('icon', [
        'type' => 'file',
        'label' => '画像をアップロード',
        'class' => 'form-control'
      ]) ?>
    </div>

    <!-- 更新ボタン -->
    <div class="d-grid mt-4">
      <?= $this->Form->button('プロフィールを更新', ['class' => 'btn btn-primary btn-lg']) ?>
    </div>

    <?= $this->Form->end() ?>
  </div>
</div>