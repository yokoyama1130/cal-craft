<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;

class WebhooksController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->request->allowMethod(['post']);
        $this->autoRender = false;
    }

    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        if (property_exists($this, 'Authentication')) {
            $this->Authentication->allowUnauthenticated(['stripe']);
        }
    }

    public function stripe()
    {
        $secret = (string)env('STRIPE_WEBHOOK_SECRET', (string)(Configure::read('Stripe.webhook_secret') ?? ''));
        if ($secret === '') {
            Log::error('Stripe webhook secret not set (empty).');

            return $this->response->withStatus(500);
        }

        $payload = (string)$this->request->input();
        $sigHeader = $this->request->getHeaderLine('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            Log::warning('Stripe webhook invalid payload: ' . $e->getMessage());

            return $this->response->withStatus(400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature failed: ' . $e->getMessage());

            return $this->response->withStatus(400);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook verify error: ' . $e->getMessage());

            return $this->response->withStatus(500);
        }

        $type = (string)($event->type ?? '');
        Log::info('Stripe event received: ' . $type);

        try {
            $Companies = $this->fetchTable('Companies');
            $Invoices = $this->fetchTable('CompanyInvoices');

            // Upsert用ヘルパ（invoice_id または pi_id で一意）
            $upsert = function(array $data) use ($Invoices, $payload) {
                $existing = null;

                if (!empty($data['stripe_invoice_id'])) {
                    $existing = $Invoices->find()
                        ->where(['stripe_invoice_id' => $data['stripe_invoice_id']])
                        ->first();
                }
                if (!$existing && !empty($data['stripe_payment_intent_id'])) {
                    $existing = $Invoices->find()
                        ->where(['stripe_payment_intent_id' => $data['stripe_payment_intent_id']])
                        ->first();
                }

                if (isset($data['amount']) && (int)$data['amount'] === 0) {
                    Log::warning('[CompanyInvoices upsert] amount resolved as 0. data=' . json_encode($data, JSON_UNESCAPED_UNICODE));
                }

                $data['raw_payload'] = $payload;

                if ($existing) {
                    $existing = $Invoices->patchEntity($existing, $data, ['validate' => false]);
                    if (!$Invoices->save($existing)) {
                        Log::error('CompanyInvoice update failed: ' . json_encode($existing->getErrors(), JSON_UNESCAPED_UNICODE));
                    }

                    return $existing;
                }

                $rec = $Invoices->newEmptyEntity();
                $rec = $Invoices->patchEntity($rec, $data, ['validate' => false]);
                if (!$Invoices->save($rec)) {
                    Log::error('CompanyInvoice create failed: ' . json_encode($rec->getErrors(), JSON_UNESCAPED_UNICODE));
                }

                return $rec;
            };

            switch ($type) {
                // —— サブスク: 請求書確定（openで先に入れておく）——
                case 'invoice.finalized': {
                    /** @var \Stripe\Invoice $inv */
                    $inv = $event->data->object;
                    $customerId = (string)($inv->customer ?? '');
                    $company = $customerId ? $Companies->find()->where(['stripe_customer_id' => $customerId])->first() : null;
                    if (!$company) break;

                    $amount = $this->resolveInvoiceAmount($inv, true);
                    $plan   = $this->resolvePlanFromInvoice($inv);

                    $upsert([
                        'company_id'             => (int)$company->id,
                        'stripe_customer_id'     => $customerId,
                        'stripe_subscription_id' => (string)($inv->subscription ?? ''),
                        'stripe_invoice_id'      => (string)($inv->id ?? ''),
                        'stripe_payment_intent_id' => (string)($inv->payment_intent ?? null),
                        'plan'                   => $plan,
                        'amount'                 => $amount,
                        'currency'               => (string)($inv->currency ?? 'jpy'),
                        'status'                 => 'open',
                        'paid_at'                => null,
                    ]);
                    break;
                }

                // —— サブスク: 支払い完了（paid で上書き）——
                case 'invoice.paid':
                case 'invoice.payment_succeeded': {
                    /** @var \Stripe\Invoice $inv */
                    $inv = $event->data->object;
                    $customerId = (string)($inv->customer ?? '');
                    $company = $customerId ? $Companies->find()->where(['stripe_customer_id' => $customerId])->first() : null;
                    if (!$company) break;

                    $amount = $this->resolveInvoiceAmount($inv, true);
                    if ($amount === 0) {
                        Log::warning('[invoice.payment_succeeded] amount=0 dump: ' . json_encode($inv->toArray()));
                    }
                    $plan = $this->resolvePlanFromInvoice($inv);

                    $upsert([
                        'company_id'             => (int)$company->id,
                        'stripe_customer_id'     => $customerId,
                        'stripe_subscription_id' => (string)($inv->subscription ?? ''),
                        'stripe_invoice_id'      => (string)($inv->id ?? ''),
                        'stripe_payment_intent_id' => (string)($inv->payment_intent ?? null),
                        'plan'                   => $plan,
                        'amount'                 => $amount,
                        'currency'               => (string)($inv->currency ?? 'jpy'),
                        'status'                 => 'paid',
                        'paid_at'                => new FrozenTime(),
                    ]);

                    // paid_until も進める
                    $firstLine   = $inv->lines->data[0] ?? null;
                    $periodEndTs = (int)($firstLine->period->end ?? $inv->period_end ?? 0);
                    if ($periodEndTs) {
                        $company->paid_until = FrozenTime::createFromTimestamp($periodEndTs);
                        $Companies->save($company);
                    }
                    break;
                }

                // —— 単発決済（都度課金）——
                case 'payment_intent.succeeded': {
                    /** @var \Stripe\PaymentIntent $pi */
                    $pi = $event->data->object;
                    $companyId = (int)($pi->metadata->company_id ?? 0);
                    $plan      = (string)($pi->metadata->target_plan ?? $pi->metadata->plan ?? 'pro');
                    $amount    = isset($pi->amount_received) ? (int)$pi->amount_received : ((int)($pi->amount ?? 0));
                    $currency  = (string)($pi->currency ?? 'jpy');
                    $piId      = (string)$pi->id;

                    if ($amount === 0) {
                        Log::warning('[payment_intent.succeeded] amount=0 dump: ' . json_encode($pi->toArray()));
                    }

                    if ($companyId > 0) {
                        $upsert([
                            'company_id'               => $companyId,
                            'stripe_customer_id'       => (string)($pi->customer ?? ''),
                            'stripe_subscription_id'   => (string)($pi->metadata->subscription_id ?? null),
                            'stripe_invoice_id'        => null,
                            'stripe_payment_intent_id' => $piId,
                            'plan'                     => $plan,
                            'amount'                   => $amount,
                            'currency'                 => $currency,
                            'status'                   => 'paid',
                            'paid_at'                  => new FrozenTime(),
                        ]);

                        // 任意：会社プランも反映
                        try {
                            $Companies = $this->fetchTable('Companies');
                            $c = $Companies->get($companyId);
                            $c = $Companies->patchEntity($c, ['plan' => $plan], ['validate' => false]);
                            $Companies->save($c);
                        } catch (\Throwable $e) {
                            Log::error('Companies plan update exception: ' . $e->getMessage());
                        }
                    } else {
                        Log::warning(sprintf('payment_intent.succeeded without company_id (PI %s)', $piId));
                    }
                    break;
                }

                default:
                    // その他イベントは必要に応じて
                    break;
            }
        } catch (\Throwable $e) {
            Log::error(sprintf('Stripe webhook handler exception (%s): %s', $type, $e->getMessage()));
            Log::error($e->getTraceAsString());
            // Stripe のリトライを止めるため 200
            return $this->response->withStatus(200);
        }

        return $this->response->withStatus(200);
    }

    /**
     * Invoice の金額を安全に解決（最小通貨単位：JPYは円）
     * - amount_paid → total → amount_due → 明細行合算（amount_total / amount）
     */
    private function resolveInvoiceAmount(object $inv, bool $allowLineSum = true): int
    {
        $amount = 0;
        if (isset($inv->amount_paid) && is_numeric($inv->amount_paid)) {
            $amount = (int)$inv->amount_paid;
        } elseif (isset($inv->total) && is_numeric($inv->total)) {
            $amount = (int)$inv->total;
        } elseif (isset($inv->amount_due) && is_numeric($inv->amount_due)) {
            $amount = (int)$inv->amount_due;
        }

        if ($amount === 0 && $allowLineSum && isset($inv->lines->data) && is_array($inv->lines->data)) {
            foreach ($inv->lines->data as $line) {
                $amount += (int)($line->amount_total ?? $line->amount ?? 0);
            }
        }
        return $amount;
    }

    /**
     * Invoice の1行目からプラン名を推定（price が object でも string でもOK）
     */
    private function resolvePlanFromInvoice(object $inv): string
    {
        $plan = 'pro';
        $firstLine = $inv->lines->data[0] ?? null;
        if ($firstLine) {
            $price = $firstLine->price ?? null; // string or object
            if (is_object($price)) {
                $plan = (string)($price->nickname ?? $price->lookup_key ?? $price->product ?? $plan);
            } elseif (is_string($price) && $price !== '') {
                $plan = $price;
            }
        }
        return $plan;
    }
}
