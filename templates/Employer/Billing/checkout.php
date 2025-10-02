<div class="container py-4" style="max-width:560px;">
  <h1 class="h5 mb-3">プラン確認</h1>
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <p class="mb-2">会社：<?= h($company->name) ?></p>
      <p class="mb-2">変更後プラン：<strong><?= h($plan) ?></strong></p>
      <p class="small text-muted mb-0">
        ※決済連携前の開発モード：このまま確定すると即時にプランが更新されます。
      </p>
    </div>
  </div>

  <?= $this->Form->create(null) ?>
    <div class="d-flex gap-2">
      <?= $this->Html->link('戻る', ['action' => 'plan'], ['class' => 'btn btn-outline-secondary']) ?>
      <?= $this->Form->button('このプランに変更する', ['class' => 'btn btn-primary']) ?>
    </div>
  <?= $this->Form->end() ?>
</div>
