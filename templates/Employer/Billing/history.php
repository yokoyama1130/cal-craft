<?php
/**
 * @var \Cake\Datasource\ResultSetInterface<\App\Model\Entity\CompanyInvoice> $invoices
 */
use Cake\I18n\FrozenTime;
?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 mb-0">請求履歴</h1>
    <?= $this->Html->link('プラン変更へ', ['action' => 'plan'], ['class' => 'btn btn-outline-secondary']) ?>
  </div>

  <?php if ($invoices->isEmpty()): ?>
    <div class="alert alert-info mb-0">請求履歴はまだありません。</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
        <tr>
          <th style="width: 180px;">日時</th>
          <th>プラン</th>
          <th class="text-end" style="width: 140px;">金額</th>
          <th style="width: 120px;">ステータス</th>
          <th style="width: 240px;">ドキュメント</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($invoices as $i): ?>
          <?php
            $dt = $i->paid_at ?: $i->created;
            // 表示をお好みで（例：YYYY/MM/DD HH:mm）
            $dtText = $dt instanceof FrozenTime ? $dt->i18nFormat('yyyy/MM/dd HH:mm') : h((string)$dt);

            $status = (string)($i->status ?? '');
            $badge = 'bg-secondary';
            if ($status === 'paid') $badge = 'bg-success';
            elseif (in_array($status, ['open','requires_payment_method'], true)) $badge = 'bg-warning text-dark';
            elseif (in_array($status, ['void','canceled'], true)) $badge = 'bg-dark';
            elseif (in_array($status, ['uncollectible','refunded'], true)) $badge = 'bg-danger';

            $amount = is_numeric($i->amount) ? (int)$i->amount : null; // JPYは最小単位＝円
          ?>
          <tr>
            <td><?= h($dtText) ?></td>
            <td><?= h($i->plan ?: '-') ?></td>
            <td class="text-end">
              <?= $amount !== null ? '¥' . number_format($amount) : '-' ?>
            </td>
            <td>
              <span class="badge <?= $badge ?>"><?= h($status ?: '-') ?></span>
            </td>
            <td class="small">
              <?php if (!empty($i->stripe_invoice_id)): ?>
                請求書：
                <?= $this->Html->link(
                      h($i->stripe_invoice_id),
                      ['prefix' => 'Employer', 'controller' => 'Billing', 'action' => 'receipt', 'invoice', $i->stripe_invoice_id],
                      ['target' => '_blank', 'rel' => 'noopener', 'escape' => true]
                    ) ?>
                <br>
              <?php endif; ?>
              <?php if (!empty($i->stripe_payment_intent_id)): ?>
                レシート：
                <?= $this->Html->link(
                      h($i->stripe_payment_intent_id),
                      ['prefix' => 'Employer', 'controller' => 'Billing', 'action' => 'receipt', 'pi', $i->stripe_payment_intent_id],
                      ['target' => '_blank', 'rel' => 'noopener', 'escape' => true]
                    ) ?>
              <?php endif; ?>
              <?php if (empty($i->stripe_invoice_id) && empty($i->stripe_payment_intent_id)): ?>
                -
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
