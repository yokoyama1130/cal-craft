<?php
/**
 * @var string $planKey
 * @var string $publishableKey
 */
?>
<div class="container py-4" style="max-width:520px;">
  <h1 class="h5 mb-3">クレジットカードでお支払い</h1>
  <?= $this->Html->meta('csrfToken', $this->request->getAttribute('csrfToken')) ?>
  <p class="text-muted">選択プラン：<strong><?= h($planKey) ?></strong></p>

  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <form id="payment-form">
        <div id="payment-element"><!-- Stripe mounts here --></div>
        <button id="submit" class="btn btn-primary w-100 mt-3">
          <span id="button-text">お支払いを確定する</span>
        </button>
        <div id="payment-message" class="mt-3 text-danger" role="alert" style="display:none;"></div>
      </form>
    </div>
  </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
(async () => {
  const stripe = Stripe('<?= h($publishableKey) ?>');
  const csrf = document.querySelector('meta[name="csrfToken"]')?.getAttribute('content');

  const resp = await fetch('<?= $this->Url->build(['prefix'=>'Employer','controller'=>'Billing','action'=>'intent',$planKey]) ?>', {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-Token': csrf ?? '',
      'Content-Type': 'application/json'
    },
    body: '{}' // 空でもOK（CakeのCSRFがPOSTボディ/ヘッダを検査）
  });

  if (!resp.ok) {
    document.getElementById('payment-message').textContent = '決済の準備に失敗しました。（' + resp.status + '）';
    document.getElementById('payment-message').style.display = 'block';
    return;
  }
  const { clientSecret } = await resp.json();
  if (!clientSecret) {
    document.getElementById('payment-message').textContent = '決済の準備に失敗しました。（clientSecret無し）';
    document.getElementById('payment-message').style.display = 'block';
    return;
  }

  const elements = stripe.elements({ clientSecret });
  const paymentElement = elements.create("payment");
  paymentElement.mount("#payment-element");

  const form = document.getElementById("payment-form");
  const message = document.getElementById("payment-message");
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    message.style.display = 'none';

    const { error } = await stripe.confirmPayment({
      elements,
      confirmParams: {
        return_url: "<?= h((string)\Cake\Core\Configure::read('Stripe.success_url') ?: 'http://localhost:8765/employer/billing/success') ?>",
      },
    });

    if (error) {
      message.textContent = error.message || '決済に失敗しました。カード情報をご確認ください。';
      message.style.display = 'block';
    }
  });
})();
</script>
