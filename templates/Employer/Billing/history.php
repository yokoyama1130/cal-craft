<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0">請求履歴</h1>
        <?= $this->Html->link('プラン変更へ', ['action' => 'plan'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>
  <?php if ($invoices->isEmpty()): ?>
    <div class="alert alert-info">請求履歴はまだありません。</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>日時</th>
            <th>プラン</th>
            <th>金額</th>
            <th>ステータス</th>
            <th>Stripe</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($invoices as $i): ?>
          <tr>
            <td><?= h($i->paid_at ?: $i->created) ?></td>
            <td><?= h($i->plan ?: '-') ?></td>
            <td>
              <?= $i->amount !== null
                ? '¥' . number_format((int)$i->amount)
                : '-' ?>
            </td>
            <td>
              <span class="badge <?= $i->status === 'paid' ? 'bg-success' : 'bg-secondary' ?>">
                <?= h($i->status ?: '-') ?>
              </span>
            </td>
            <td class="small">
              <?php if ($i->stripe_invoice_id): ?>
                Invoice: <?= h($i->stripe_invoice_id) ?><br>
              <?php endif; ?>
              <?php if ($i->stripe_payment_intent_id): ?>
                PI: <?= h($i->stripe_payment_intent_id) ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?= $this->element('pagination') ?>
  <?php endif; ?>
</div>
