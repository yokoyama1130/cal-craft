<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Log\Log;
use Cake\Core\Configure;

class WebhooksController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        // CSRF/認証の対象外にする（必要なら Middleware 側で除外）
        $this->getEventManager()->on('Controller.initialize', function(EventInterface $event){});
        $this->request->allowMethod(['post']);
        $this->autoRender = false;
    }

    public function stripe()
    {
        $secret = (string)(Configure::read('Stripe.webhook_secret') ?? '');
        if ($secret === '') {
            Log::error('Stripe webhook secret not set.');
            return $this->response->withStatus(500);
        }

        $payload    = (string)$this->request->input();
        $sigHeader  = $this->request->getHeaderLine('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return $this->response->withStatus(400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            return $this->response->withStatus(400);
        }

        // 支払い成功の種類：
        // - Payment Element（PaymentIntent）→ 'payment_intent.succeeded'
        // - サブスク（Invoice）            → 'invoice.payment_succeeded'
        $type = $event->type;

        if ($type === 'payment_intent.succeeded') {
            /** @var \Stripe\PaymentIntent $pi */
            $pi = $event->data->object;

            $companyId = (int)($pi->metadata->company_id ?? 0);
            $plan      = (string)($pi->metadata->plan ?? 'pro');
            $amount    = (int)($pi->amount_received ?? $pi->amount ?? 0);
            $currency  = (string)($pi->currency ?? 'jpy');
            $extId     = (string)$pi->id;

            if ($companyId > 0) {
                // 1) 請求履歴 保存
                $Invoices = $this->fetchTable('CompanyInvoices');
                $exists = $Invoices->find()->where(['external_id' => $extId])->first();
                if (!$exists) {
                    $inv = $Invoices->newEntity([
                        'company_id'  => $companyId,
                        'provider'    => 'stripe',
                        'external_id' => $extId,
                        'amount'      => $amount,
                        'currency'    => $currency,
                        'status'      => 'succeeded',
                        'paid_at'     => new \Cake\I18n\FrozenTime(), // または $pi->created から
                        'plan'        => $plan,
                        'raw'         => json_encode($pi), // テキスト列があれば
                    ]);
                    $Invoices->save($inv);
                }

                // 2) プラン更新（必要なら）
                $Companies = $this->fetchTable('Companies');
                $c = $Companies->get($companyId);
                $c->plan = $plan;
                $Companies->save($c);
            }

        } elseif ($type === 'invoice.payment_succeeded') {
            /** @var \Stripe\Invoice $invoice */
            $invoice  = $event->data->object;
            $customer = (string)($invoice->customer ?? '');
            $amount   = (int)($invoice->amount_paid ?? 0);
            $currency = (string)($invoice->currency ?? 'jpy');
            $extId    = (string)$invoice->id;
            $plan     = (string)($invoice->lines->data[0]->price->nickname ?? 'pro'); // 任意

            // customer → company を逆引きする必要あり（stripe_customer_id を Companies に保存しておく）
            $Companies = $this->fetchTable('Companies');
            $company = $Companies->find()->where(['stripe_customer_id' => $customer])->first();
            if ($company) {
                $Invoices = $this->fetchTable('CompanyInvoices');
                $exists = $Invoices->find()->where(['external_id' => $extId])->first();
                if (!$exists) {
                    $inv = $Invoices->newEntity([
                        'company_id'  => (int)$company->id,
                        'provider'    => 'stripe',
                        'external_id' => $extId,
                        'amount'      => $amount,
                        'currency'    => $currency,
                        'status'      => 'succeeded',
                        'paid_at'     => new \Cake\I18n\FrozenTime(),
                        'plan'        => $plan,
                        'raw'         => json_encode($invoice),
                    ]);
                    $Invoices->save($inv);
                }

                // ついでにプラン更新するならここで
                // $company->plan = $plan; $Companies->save($company);
            }
        }

        return $this->response->withStatus(200);
    }
}
