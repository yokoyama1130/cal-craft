<?php
declare(strict_types=1);

namespace App\Controller\Employer;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\FrozenTime;
use Cake\Routing\Router;
use Stripe\StripeClient;

class BillingController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        if ($this->request->getParam('action') === 'webhook') {
            if ($this->components()->has('FormProtection')) {
                $this->components()->unload('FormProtection');
            }
            if ($this->components()->has('Security')) {
                $this->components()->unload('Security');
            }
        }
    }

    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        if ($this->request->getParam('action') === 'webhook') {
            if ($this->components()->has('FormProtection')) {
                $this->FormProtection->disable();
            }
            if ($this->components()->has('Security')) {
                $this->Security->setConfig('validatePost', false);
                $this->Security->setConfig('unlockedActions', ['webhook']);
            }
            if (property_exists($this, 'Authentication')) {
                $this->Authentication->allowUnauthenticated(['webhook']);
            }
        }
    }

public function plan()
{
    $auth = $this->Authentication->getIdentity();
    if (!$auth) return $this->redirect('/employer/login');

    $Companies = $this->fetchTable('Companies');
    $company   = $Companies->get((int)$auth->id);

    $plans = [
        'free'       => ['label'=>'Free','price'=>0,'features'=>['基本機能','当月 1ユーザーに先出しメッセージ']],
        'pro'        => ['label'=>'Pro','price'=>5000,'features'=>['高度機能','当月 100ユーザーに先出しメッセージ']],
        'enterprise' => ['label'=>'Enterprise','price'=>50000,'features'=>['無制限','SLA/請求書対応']],
    ];

    // ★ サブスクの次回更新情報を取得
    $hasSub = !empty($company->stripe_subscription_id);
    $nextRenewAt = null;         // FrozenTime|null
    $willAutoRenew = null;       // bool|null（サブスク無い場合は null）

    if ($hasSub) {
        try {
            \Stripe\Stripe::setApiKey((string)\Cake\Core\Configure::read('Stripe.secret_key'));
            $sub = \Stripe\Subscription::retrieve($company->stripe_subscription_id);
            if (!empty($sub->current_period_end)) {
                $nextRenewAt = \Cake\I18n\FrozenTime::createFromTimestamp($sub->current_period_end);
            }
            $willAutoRenew = !((bool)($sub->cancel_at_period_end ?? false)) && ((string)$sub->status === 'active');
        } catch (\Throwable $e) {
            // 失敗しても画面表示は継続
            $this->log('Stripe Subscription retrieve failed: '.$e->getMessage(), 'warning');
        }
    }

    $this->set(compact('company', 'plans', 'hasSub', 'nextRenewAt', 'willAutoRenew'));
}


    public function checkout(string $plan)
    {
        $this->request->allowMethod(['post']);
        $this->disableAutoRender();

        $priceMap = (array)Configure::read('Stripe.price_map');
        $hasPrice = isset($priceMap[$plan]) && str_starts_with($priceMap[$plan], 'price_');

        $amountMap = ['pro' => 5000, 'enterprise' => 50000];
        if (!$hasPrice && !isset($amountMap[$plan])) {
            throw new BadRequestException('invalid plan');
        }

        \Stripe\Stripe::setApiKey((string)Configure::read('Stripe.secret_key'));

        $auth = $this->Authentication->getIdentity();
        if (!$auth) throw new BadRequestException('auth required');
        $companyId = (int)$auth->id;

        $Companies = $this->fetchTable('Companies');
        $c = $Companies->get($companyId);

        if (empty($c->stripe_customer_id)) {
            $customer = \Stripe\Customer::create([
                'name'     => $c->name ?? ('Company#'.$c->id),
                'metadata' => ['company_id' => (string)$c->id],
            ]);
            $c->stripe_customer_id = $customer->id;
            $Companies->save($c);
        }

        $successUrl = Router::url(['prefix'=>'Employer','controller'=>'Billing','action'=>'success'], true) . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl  = Router::url(['prefix'=>'Employer','controller'=>'Billing','action'=>'cancel'], true);

        $lineItem = $hasPrice
            ? ['price' => $priceMap[$plan], 'quantity' => 1]
            : [
                'price_data'=>[
                    'currency'=>'jpy',
                    'unit_amount'=>$amountMap[$plan],
                    'recurring'=>['interval'=>'month'],
                    'product_data'=>['name'=>"OrcaFolio {$plan} plan"],
                ],
                'quantity'=>1,
            ];

        $session = \Stripe\Checkout\Session::create([
            'mode' => 'subscription',
            'customer' => $c->stripe_customer_id,
            'line_items'=>[$lineItem],
            'success_url'=>$successUrl,
            'cancel_url'=>$cancelUrl,
            'allow_promotion_codes'=>true,
            'client_reference_id'=>(string)$companyId,
            'metadata'=>['company_id'=>(string)$companyId,'target_plan'=>$plan],
        ]);

        return $this->redirect($session->url, 303);
    }

    public function success()
    {
        $this->request->allowMethod(['get']);

        $secret = (string)Configure::read('Stripe.secret_key');
        $stripe = new StripeClient($secret);

        $sid = (string)$this->request->getQuery('session_id');
        if ($sid === '') {
            $this->Flash->error('セッションIDが見つかりません。');
            return $this->redirect(['action'=>'plan']);
        }

        try {
            $sess = $stripe->checkout->sessions->retrieve(
                $sid,
                ['expand' => ['subscription']]
            );
        } catch (\Throwable $e) {
            $this->log("Checkout session retrieve failed: ".$e->getMessage(), 'error');
            $this->Flash->error('セッションの確認に失敗しました。');
            return $this->redirect(['action'=>'plan']);
        }

        $companyId = (int)($sess->metadata->company_id ?? $sess->client_reference_id ?? 0);
        $plan      = (string)($sess->metadata->target_plan ?? '');
        $customer  = (string)($sess->customer ?? '');
        $subId     = is_object($sess->subscription) ? $sess->subscription->id : (string)$sess->subscription;

        if ($companyId && $subId) {
            $Companies = $this->fetchTable('Companies');
            $c = $Companies->get($companyId);
            if ($customer) $c->stripe_customer_id = $customer;
            $c->stripe_subscription_id = $subId;
            if ($plan) $c->plan = $plan;
            if (is_object($sess->subscription) && !empty($sess->subscription->current_period_end)) {
                $c->paid_until = FrozenTime::createFromTimestamp($sess->subscription->current_period_end);
            }
            $Companies->save($c);

            $this->Flash->success('処理が完了しました。プランは反映に数秒かかる場合があります。');
            return $this->redirect(['action'=>'plan']);
        }

        $this->Flash->error('セッション情報が不完全です。');
        return $this->redirect(['action'=>'plan']);
    }

    /**
     * Stripe Webhook
     * POST /employer/billing/webhook
     */
    public function webhook()
    {
        $this->request->allowMethod(['post']);

        // 到達ログ
        $req = $this->request;
        $sig = $req->getHeaderLine('Stripe-Signature');
        $raw = file_get_contents('php://input') ?: '';
        $wh  = (string)(Configure::read('Stripe.webhook_secret') ?? '');

        file_put_contents(LOGS.'webhook_debug.log', json_encode([
            'arrived'       => true,
            'path'          => $req->getPath(),
            'has_sig'       => $sig !== '',
            'raw_len'       => strlen($raw),
            'whsec_len'     => strlen($wh),
            'whsec_preview' => $wh ? (substr($wh, 0, 6) . '...' . substr($wh, -6)) : null,
        ], JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

        if ($wh === '' || $sig === '' || $raw === '') {
            file_put_contents(LOGS.'webhook_debug.log', "missing config/header/body\n", FILE_APPEND);
            return $this->response->withStatus(400)->withStringBody('invalid');
        }

        try {
            $event = \Stripe\Webhook::constructEvent($raw, $sig, $wh);
        } catch (\Throwable $e) {
            file_put_contents(LOGS.'webhook_debug.log', json_encode(['verify_error' => $e->getMessage()], JSON_PRETTY_PRINT)."\n", FILE_APPEND);
            return $this->response->withStatus(400)->withStringBody('bad sig');
        }

        file_put_contents(LOGS.'webhook_debug.log', json_encode([
            'verified' => true,
            'type'     => $event->type ?? null,
            'id'       => $event->id ?? null,
        ], JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

        $Companies = $this->fetchTable('Companies');
        $Invoices  = $this->fetchTable('CompanyInvoices');

        // upsert 用クロージャ
        $saveInvoice = function(array $data) use ($Invoices, $raw) {
            $existing = null;

            if (!empty($data['stripe_invoice_id'])) {
                $existing = $Invoices->find()->where(['stripe_invoice_id' => $data['stripe_invoice_id']])->first();
            }
            if (!$existing && !empty($data['stripe_payment_intent_id'])) {
                $existing = $Invoices->find()->where(['stripe_payment_intent_id' => $data['stripe_payment_intent_id']])->first();
            }

            // 0円ならログ
            if (isset($data['amount']) && (int)$data['amount'] === 0) {
                \Cake\Log\Log::warning('[CompanyInvoices upsert] amount=0 data='.json_encode($data, JSON_UNESCAPED_UNICODE));
            }

            if ($existing) {
                $existing = $Invoices->patchEntity($existing, $data + ['raw_payload' => $raw]);
                if (!$Invoices->save($existing)) {
                    \Cake\Log\Log::error('CompanyInvoice update failed: ' . json_encode($existing->getErrors(), JSON_UNESCAPED_UNICODE));
                }
                return;
            }

            $rec = $Invoices->newEmptyEntity();
            $rec = $Invoices->patchEntity($rec, $data + ['raw_payload' => $raw]);
            if (!$Invoices->save($rec)) {
                \Cake\Log\Log::error('CompanyInvoice create failed: ' . json_encode($rec->getErrors(), JSON_UNESCAPED_UNICODE));
            }
        };

        switch ($event->type ?? '') {
            case 'checkout.session.completed': {
                $sess = $event->data->object ?? null;
                if ($sess) {
                    $companyId = (int)($sess->metadata->company_id ?? $sess->client_reference_id ?? 0);
                    $plan      = (string)($sess->metadata->target_plan ?? 'pro');
                    $customer  = (string)($sess->customer ?? '');
                    $subId     = is_object($sess->subscription) ? $sess->subscription->id : (string)($sess->subscription ?? '');

                    if ($companyId && $subId) {
                        $c = $Companies->get($companyId);
                        if ($customer) $c->stripe_customer_id = $customer;
                        $c->stripe_subscription_id = $subId;
                        $c->plan = $plan;

                        try {
                            \Stripe\Stripe::setApiKey((string)Configure::read('Stripe.secret_key'));
                            $sub = \Stripe\Subscription::retrieve($subId);
                            if (!empty($sub->current_period_end)) {
                                $c->paid_until = FrozenTime::createFromTimestamp($sub->current_period_end);
                            }
                        } catch (\Throwable $e) {
                            // 読み取り失敗は無視
                        }
                        $Companies->save($c);
                    }
                }
                break;
            }

            // 確定時に open で upsert（あとで paid で上書き）
            case 'invoice.finalized': {
                $inv = $event->data->object ?? null;
                if ($inv) {
                    $customer = (string)($inv->customer ?? '');
                    $company = $customer ? $Companies->find()->where(['stripe_customer_id' => $customer])->first() : null;
                    if ($company) {
                        $amount = $this->resolveInvoiceAmount($inv, /*allowLineSum=*/true);
                        $plan   = $this->resolvePlanFromInvoice($inv);

                        $saveInvoice([
                            'company_id'             => (int)$company->id,
                            'stripe_customer_id'     => $customer,
                            'stripe_subscription_id' => (string)($inv->subscription ?? ''),
                            'stripe_invoice_id'      => (string)($inv->id ?? ''),
                            'plan'                   => $plan,
                            'amount'                 => $amount,
                            'currency'               => (string)($inv->currency ?? 'jpy'),
                            'status'                 => 'open',
                            'paid_at'                => null,
                        ]);
                    }
                }
                break;
            }

            case 'invoice.paid':
            case 'invoice.payment_succeeded': {
                $inv = $event->data->object ?? null;
                if ($inv) {
                    $customer = (string)($inv->customer ?? '');
                    $company = $customer ? $Companies->find()->where(['stripe_customer_id' => $customer])->first() : null;

                    if ($company) {
                        $amount = $this->resolveInvoiceAmount($inv, /*allowLineSum=*/true);
                        if ($amount === 0) {
                            \Cake\Log\Log::warning('[invoice.payment_succeeded] amount=0 dump: '.json_encode($inv->toArray()));
                        }

                        $plan = $this->resolvePlanFromInvoice($inv);

                        $saveInvoice([
                            'company_id'             => (int)$company->id,
                            'stripe_customer_id'     => $customer,
                            'stripe_subscription_id' => (string)($inv->subscription ?? ''),
                            'stripe_invoice_id'      => (string)($inv->id ?? ''),
                            'plan'                   => $plan,
                            'amount'                 => $amount,
                            'currency'               => (string)($inv->currency ?? 'jpy'),
                            'status'                 => 'paid',
                            'paid_at'                => date('Y-m-d H:i:s'),
                        ]);

                        $firstLine   = $inv->lines->data[0] ?? null;
                        $periodEndTs = (int)($firstLine->period->end ?? $inv->period_end ?? 0);
                        if ($periodEndTs) {
                            $company->paid_until = FrozenTime::createFromTimestamp($periodEndTs);
                            $Companies->save($company);
                        }
                    }
                }
                break;
            }

            case 'customer.subscription.updated': {
                $sub = $event->data->object ?? null;
                if ($sub) {
                    $c = $Companies->find()->where(['stripe_subscription_id' => $sub->id])->first();
                    if ($c) {
                        if (!empty($sub->current_period_end)) {
                            $c->paid_until = FrozenTime::createFromTimestamp($sub->current_period_end);
                        }
                        if (in_array((string)$sub->status, ['canceled','unpaid'], true)) {
                            $c->plan = 'free';
                            $c->stripe_subscription_id = null;
                        }
                        $Companies->save($c);
                    }
                }
                break;
            }

            case 'customer.subscription.deleted': {
                $sub = $event->data->object ?? null;
                if ($sub) {
                    $c = $Companies->find()->where(['stripe_subscription_id' => $sub->id])->first();
                    if ($c) {
                        $c->plan = 'free';
                        $c->stripe_subscription_id = null;
                        $Companies->save($c);
                    }
                }
                break;
            }

            // 単発（今は使わないかもだが堅牢に）
            case 'payment_intent.succeeded': {
                $pi = $event->data->object ?? null;
                if ($pi) {
                    $companyId  = (int)($pi->metadata->company_id ?? 0);
                    $targetPlan = (string)($pi->metadata->target_plan ?? ($pi->metadata->plan ?? ''));

                    $amount = isset($pi->amount_received) ? (int)$pi->amount_received :
                              (isset($pi->amount) ? (int)$pi->amount : 0);

                    if ($amount === 0) {
                        $this->log('[payment_intent.succeeded] amount=0 payload='.json_encode($pi->toArray()), 'warning');
                    }

                    if ($companyId && $targetPlan) {
                        $company = $Companies->get($companyId);
                        $company->plan = $targetPlan;
                        $Companies->save($company);

                        $saveInvoice([
                            'company_id'               => $companyId,
                            'stripe_customer_id'       => (string)($pi->customer ?? ''),
                            'stripe_payment_intent_id' => (string)($pi->id ?? ''),
                            'plan'                     => $targetPlan,
                            'amount'                   => $amount,
                            'currency'                 => (string)($pi->currency ?? 'jpy'),
                            'status'                   => 'paid',
                            'paid_at'                  => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
                break;
            }

            default:
                // noop
                break;
        }

        return $this->response->withType('text/plain')->withStringBody('ok');
    }

    public function cancel()
    {
        $this->Flash->error('決済をキャンセルしました。');
        return $this->redirect(['action' => 'plan']);
    }

    public function cancelAtPeriodEnd()
    {
        $this->request->allowMethod(['post']);
        \Stripe\Stripe::setApiKey((string)Configure::read('Stripe.secret_key'));

        $auth = $this->Authentication->getIdentity();
        if (!$auth) throw new BadRequestException('auth required');

        $Companies = $this->fetchTable('Companies');
        $company   = $Companies->get((int)$auth->id);

        if (empty($company->stripe_subscription_id)) throw new BadRequestException('no subscription');

        $sub = \Stripe\Subscription::update($company->stripe_subscription_id, [
            'cancel_at_period_end' => true,
        ]);

        if (!empty($sub->current_period_end)) {
            $company->paid_until = FrozenTime::createFromTimestamp($sub->current_period_end);
            $Companies->save($company);
        }

        $this->Flash->success('今期末で解約を予約しました。');
        return $this->redirect(['action' => 'plan']);
    }

    public function cancelNow()
    {
        $this->request->allowMethod(['post']);

        $auth = $this->Authentication->getIdentity();
        if (!$auth) throw new BadRequestException('auth required');

        $Companies = $this->fetchTable('Companies');
        $company   = $Companies->get((int)$auth->id);
        if (empty($company->stripe_subscription_id)) throw new BadRequestException('no subscription');

        $stripe = new \Stripe\StripeClient((string)Configure::read('Stripe.secret_key'));
        // ここが修正ポイント：静的呼び出しではなくクライアント経由
        $stripe->subscriptions->cancel($company->stripe_subscription_id, []);

        $company->plan = 'free';
        $company->stripe_subscription_id = null;
        $Companies->save($company);

        $this->Flash->success('サブスクリプションを即時解約しました。');
        return $this->redirect(['action' => 'plan']);
    }

    public function history()
    {
        $auth = $this->Authentication->getIdentity();
        if (!$auth) return $this->redirect('/employer/login');

        $Invoices = $this->fetchTable('CompanyInvoices');
        $q = $Invoices->find()
            ->where(['company_id' => (int)$auth->id])
            ->order(['paid_at' => 'DESC', 'created' => 'DESC']);

        $this->paginate = ['limit' => 20];
        $invoices = $this->paginate($q);

        $this->set(compact('invoices'));
    }

    public function receipt(string $kind, string $id)
    {
        $this->request->allowMethod(['get']);

        $auth = $this->Authentication->getIdentity();
        if (!$auth) return $this->redirect('/employer/login');

        $Invoices = $this->fetchTable('CompanyInvoices');

        if ($kind === 'invoice') {
            $rec = $Invoices->find()->where(['company_id' => (int)$auth->id, 'stripe_invoice_id' => $id])->first();
        } elseif ($kind === 'pi') {
            $rec = $Invoices->find()->where(['company_id' => (int)$auth->id, 'stripe_payment_intent_id' => $id])->first();
        } else {
            throw new BadRequestException('invalid kind');
        }

        if (!$rec) {
            $this->Flash->error('対象の履歴が見つかりません。');
            return $this->redirect(['action' => 'history']);
        }

        $secret = (string)(Configure::read('Stripe.secret_key') ?? '');
        if ($secret === '') {
            $this->Flash->error('Stripeの設定が未構成です。');
            return $this->redirect(['action' => 'history']);
        }

        $stripe = new StripeClient($secret);

        try {
            if ($kind === 'invoice') {
                $inv = $stripe->invoices->retrieve($id, []);
                $url = $inv->invoice_pdf ?: $inv->hosted_invoice_url ?: null;
                if ($url) return $this->redirect($url, 302);
            } else {
                $pi = $stripe->paymentIntents->retrieve($id, ['expand' => ['latest_charge']]);
                $url = $pi->latest_charge->receipt_url ?? null;
                if ($url) return $this->redirect($url, 302);
            }
        } catch (\Throwable $e) {
            $this->log('Stripe receipt redirect failed: '.$e->getMessage(), 'error');
        }

        $this->Flash->error('領収書のURLが見つかりません。');
        return $this->redirect(['action' => 'history']);
    }

    /**
     * Invoice の金額を安全に解決（最小通貨単位：JPYなら円）
     */
    protected function resolveInvoiceAmount(object $inv, bool $allowLineSum = true): int
    {
        $amount = 0;
        if (isset($inv->amount_paid))        $amount = (int)$inv->amount_paid;
        elseif (isset($inv->total))          $amount = (int)$inv->total;
        elseif (isset($inv->amount_due))     $amount = (int)$inv->amount_due;

        if ($amount === 0 && $allowLineSum && isset($inv->lines->data) && is_array($inv->lines->data)) {
            foreach ($inv->lines->data as $line) {
                $amount += (int)($line->amount_total ?? $line->amount ?? 0);
            }
        }
        return $amount;
    }

    /**
     * Invoice の1行目からプラン名を推定
     */
    protected function resolvePlanFromInvoice(object $inv): string
    {
        $plan = 'pro';
        $firstLine = $inv->lines->data[0] ?? null;
        if ($firstLine) {
            $price = $firstLine->price ?? null;
            if (is_object($price)) {
                $plan = (string)($price->nickname ?? $price->lookup_key ?? $price->product ?? $plan);
            } elseif (is_string($price) && $price !== '') {
                $plan = $price;
            }
        }
        return $plan;
    }
}
