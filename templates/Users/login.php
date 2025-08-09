<div class="container mt-5" style="max-width: 500px;">
    <h2 class="mb-4">ログイン</h2>
    <?= $this->Form->create() ?>
        <div class="mb-3">
            <?= $this->Form->control('email', ['class' => 'form-control', 'label' => 'Email']) ?>
        </div>
        <div class="mb-3">
            <?= $this->Form->control('password', ['class' => 'form-control', 'label' => 'Password']) ?>
        </div>
        <?= $this->Form->button('ログイン', ['class' => 'btn btn-primary w-100']) ?>
        <p class="mt-3">
        認証メールが届かない場合は
        <?= $this->Html->link('こちら', ['action' => 'resendVerification']) ?> から再送できます。
        </p>
    <?= $this->Form->end() ?>
</div>
