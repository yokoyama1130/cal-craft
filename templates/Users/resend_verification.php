<h2 class="mb-3">確認メールを再送する</h2>
<p class="text-muted">登録したメールアドレスを入力してください。確認メールを再送します。</p>

<div class="card p-4 shadow-sm" style="max-width:480px">
  <?= $this->Form->create(null) ?>
    <div class="mb-3">
      <?= $this->Form->control('email', [
        'label' => 'メールアドレス',
        'type' => 'email',
        'required' => true,
        'class' => 'form-control',
      ]) ?>
    </div>
    <?= $this->Form->button('再送する', ['class' => 'btn btn-primary']) ?>
    <?= $this->Html->link('ログインに戻る', ['action' => 'login'], ['class' => 'btn btn-link ms-2']) ?>
  <?= $this->Form->end() ?>
</div>
