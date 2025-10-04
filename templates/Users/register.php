<div class="container mt-5" style="max-width: 500px;">
    <h2 class="mb-4 fw-semibold text-center">新規登録</h2>

    <?= $this->Form->create($user, ['class' => 'needs-validation']) ?>

    <div class="mb-3">
        <?= $this->Form->control('name', [
            'label' => 'お名前',
            'class' => 'form-control',
            'required' => true,
        ]) ?>
    </div>

    <div class="mb-3">
        <?= $this->Form->control('email', [
            'label' => 'メールアドレス',
            'type' => 'email',
            'class' => 'form-control',
            'required' => true,
        ]) ?>
    </div>

    <div class="mb-3">
        <?= $this->Form->control('password', [
            'label' => 'パスワード',
            'type' => 'password',
            'class' => 'form-control',
            'required' => true,
        ]) ?>
    </div>

    <div class="d-grid">
        <?= $this->Form->button('登録', ['class' => 'btn btn-primary btn-lg']) ?>
    </div>

    <?= $this->Form->end() ?>
    <p class="mt-3">
        法人の方は
        <?= $this->Html->link('こちら', ['controller' => 'Companies', 'action' => 'add']) ?>
        から新規登録をお願いいたします。
    </p>
</div>
