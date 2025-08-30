<?php
declare(strict_types=1);

namespace App\Controller\Employer;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Stripe\StripeClient;
use Stripe\Webhook;

class BillingController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        // Employer 側は通常ログイン必須。Webhook だけは未認証で受ける
        $this->Authentication->addUnauthenticatedActions(['webhook']);

        // Employer 用テンプレートディレクトリ
        $this->viewBuilder()->setTemplatePath('Employer/Billing');
    }

    /**
     * CSRF/FormProtection の影響を避けたいアクションを除外
     *  - intent: JS fetch から叩く（Cookie 無しを想定することがある）
     *  - webhook: 外部（Stripe）から POST
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        // FormProtection（フォーム改ざん防止）の除外
        if (property_exists($this, 'FormProtection')) {
            $this->FormProtection->setConfig('unlockedActions', ['intent', 'webhook']);
        }
        // CsrfProtectionMiddleware の除外は Application 側で
        // ->add(new CsrfProtectionMiddleware([... 'whitelistCallback' => fn($req) => $req->getPath()==='/employer/billing/webhook' ]))
    }

    /**
     * プラン一覧
     */
    public function plan()
    {
        $auth = $this->Authentication->getIdentity();
        if (!$auth) {
            return $this->redirect('/employer/login');
        }

        // Identity の古さ対策で取り直し
        $Companies = $this->fetchTable('Companies');
        $company   = $Companies->get($auth->id);

        $plans = [
            'free' => [
                'label'    => 'Free',
                'price'    => 0,
                'features' => ['基本機能', '当月 1ユーザーに先出しメッセージ'],
            ],
            'pro' => [
                'label'    => 'Pro',
                'price'    => 9800,
                'features' => ['高度機能', '当月 100ユーザーに先出しメッセージ'],
            ],
            'enterprise' => [
                'label'    => 'Enterprise',
                'price'    => null,
                'features' => ['無制限', 'SLA/請求書対応'],
            ],
        ];

        $this->set(compact('company', 'plans'));
    }

    /**
     * プラン変更開始（Free は即時・有料は Stripe Checkout）
     * /employer/billing/checkout/{planKey}
     */
    public function checkout(string $planKey)
    {
        $auth = $this->Authentication->getIdentity();
        if (!$auth) {
            return $this->redirect('/employer/login');
        }

        $valid = ['free','pro','enterprise'];
        if (!in_array($planKey, $valid, true)) {
            throw new BadRequestException('Invalid plan.');
        }

        $Companies = $this->fetchTable('Companies');
        $company   = $Companies->get($auth->id);

        // Free は即時反映
        if ($planKey === 'free') {
            $company->plan = 'free';
            $company->stripe_subscription_id = null;
            if ($Companies->save($company)) {
                $this->Authentication->setIdentity($Companies->get($auth->id));
                $this->Flash->success('Freeプランに変更しました。');
            } else {
                $this->Flash->error('プラン変更に失敗しました。');
            }
            return $this->redirect(['action' => 'plan']);
        }

        // 有料：Stripe Checkout（定期課金も想定）
        $cfg        = (array)Configure::read('Stripe');
        $secret     = $cfg['secret']      ?? null;
        $priceMap   = $cfg['price_map']   ?? [];
        $successUrl = $cfg['success_url'] ?? null;
        $cancelUrl  = $cfg['cancel_url']  ?? null;

        $priceId = $priceMap[$planKey] ?? null;
        if (!$secret || !$priceId || !$successUrl || !$cancelUrl) {
            // 設定不足 → 開発モードとして即時反映
            $company->plan = $planKey;
            if ($Companies->save($company)) {
                $this->Authentication->setIdentity($Companies->get($auth->id));
                $this->Flash->success("（開発モード）プランを {$planKey} に変更しました。");
            } else {
                $this->Flash->error('プラン変更に失敗しました。');
            }
            return $this->redirect(['action' => 'plan']);
        }

        $stripe = new StripeClient($secret);

        // Customer 再利用/作成
        $customerId = $company->stripe_customer_id ?: null;
        if (!$customerId) {
            $customer = $stripe->customers->create([
                'name'     => (string)$company->name,
                'email'    => $company->auth_email ?: null,
                'metadata' => ['company_id' => (string)$company->id],
            ]);
            $customerId = $customer->id;
            $company->stripe_customer_id = $customerId;
            $Companies->save($company);
        }

        $session = $stripe->checkout->sessions->create([
            'mode'        => 'subscription',
            'customer'    => $customerId,
            'line_items'  => [[ 'price' => $priceId, 'quantity' => 1 ]],
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
            'metadata'    => [
                'company_id'  => (string)$company->id,
                'target_plan' => $planKey,
            ],
        ]);

        return $this->redirect($session->url);
    }

    /**
     * カード入力ページ（Payment Element）
     * /employer/billing/pay/{planKey}
     */
    public function pay(string $planKey)
    {
        $auth = $this->Authentication->getIdentity();
        if (!$auth) {
            return $this->redirect('/employer/login');
        }

        $valid = ['pro','enterprise'];
        if (!in_array($planKey, $valid, true)) {
            throw new BadRequestException('Invalid plan.');
        }

        $pk = (string)(Configure::read('Stripe.publishable') ?? '');
        if (!$pk) {
            $this->Flash->error('Stripeの公開鍵が未設定です。');
            return $this->redirect(['action' => 'plan']);
        }

        $this->set([
            'planKey'        => $planKey,
            'publishableKey' => $pk,
        ]);
    }

    /**
     * JS から呼ぶ PaymentIntent 発行 API
     * /employer/billing/intent/{planKey} [POST]
     */
    public function intent(string $planKey)
    {
        $this->request->allowMethod(['post']);

        $company = $this->Authentication->getIdentity();
        if (!$company) {
            return $this->response->withStatus(401);
        }

        // 単発課金の金額マップ（例）
        $amountMap = ['pro' => 9800, 'enterprise' => 20000]; // JPY
        $amount = $amountMap[$planKey] ?? null;
        if (!$amount) {
            return $this->response->withStatus(400);
        }

        $cfg = (array)Configure::read('Stripe');
        $secret = $cfg['secret'] ?? null;
        if (!$secret) {
            return $this->response->withStatus(500);
        }

        $stripe = new StripeClient($secret);

        // 顧客作成/再利用
        $customerId = $company->stripe_customer_id ?: null;
        if (!$customerId) {
            $c = $stripe->customers->create([
                'name'     => (string)$company->name,
                'email'    => $company->auth_email ?: null,
                'metadata' => ['company_id' => (string)$company->id],
            ]);
            $customerId = $c->id;

            $Companies = $this->fetchTable('Companies');
            $rec = $Companies->get($company->id);
            $rec->stripe_customer_id = $customerId;
            $Companies->save($rec);
        }

        // PaymentIntent 作成（3DS 対応）
        $pi = $stripe->paymentIntents->create([
            'amount'   => $amount,
            'currency' => 'jpy',
            'customer' => $customerId,
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'company_id'  => (string)$company->id,
                'target_plan' => $planKey,
                'type'        => 'one_time_upgrade',
            ],
        ]);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['clientSecret' => $pi->client_secret]));
    }

    /**
     * Stripe Webhook（外部からの POST）
     * /employer/billing/webhook
     */
    public function webhook()
    {
        $this->request->allowMethod(['post']);

        $secret   = (string)(Configure::read('Stripe.secret') ?? '');
        $whSecret = (string)(Configure::read('Stripe.webhook_secret') ?? '');
        if (!$secret || !$whSecret) {
            return $this->response->withStatus(400)->withStringBody('Stripe keys not configured');
        }

        $payload  = (string)$this->request->getBody()->getContents();
        $sig      = $this->request->getHeaderLine('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sig, $whSecret);
        } catch (\Throwable $e) {
            return $this->response->withStatus(400)->withStringBody('Invalid signature');
        }

        $Companies = $this->fetchTable('Companies');
        $Invoices  = $this->fetchTable('CompanyInvoices');

        // 請求履歴保存の小ヘルパ
        $saveInvoice = function(array $data) use ($Invoices, $payload) {
            $rec = $Invoices->newEmptyEntity();
            $rec = $Invoices->patchEntity($rec, $data + ['raw_payload' => $payload]);
            $Invoices->save($rec);
            return $rec;
        };

        switch ($event->type) {
            // Payment Element 都度課金の成功
            case 'payment_intent.succeeded':
                /** @var \Stripe\PaymentIntent $pi */
                $pi = $event->data->object;
                $companyId  = (int)($pi->metadata->company_id ?? 0);
                $targetPlan = (string)($pi->metadata->target_plan ?? '');

                if ($companyId && $targetPlan) {
                    $company = $Companies->get($companyId);
                    $company->plan = $targetPlan;
                    $Companies->save($company);

                    $saveInvoice([
                        'company_id'              => $companyId,
                        'stripe_customer_id'      => (string)$pi->customer,
                        'stripe_payment_intent_id'=> (string)$pi->id,
                        'plan'                    => $targetPlan,
                        'amount'                  => (int)$pi->amount,
                        'currency'                => (string)$pi->currency,
                        'status'                  => 'paid',
                        'paid_at'                 => date('Y-m-d H:i:s'),
                    ]);
                }
                break;

            // サブスク運用の場合（必要に応じて運用）
            case 'invoice.payment_succeeded':
                /** @var \Stripe\Invoice $inv */
                $inv = $event->data->object;

                $company = $Companies->find()
                    ->where(['stripe_customer_id' => $inv->customer])
                    ->first();

                if ($company) {
                    $saveInvoice([
                        'company_id'              => (int)$company->id,
                        'stripe_customer_id'      => (string)$inv->customer,
                        'stripe_subscription_id'  => (string)($inv->subscription ?? ''),
                        'stripe_invoice_id'       => (string)$inv->id,
                        'plan'                    => (string)($inv->lines->data[0]->plan->nickname ?? ''),
                        'amount'                  => (int)$inv->amount_paid,
                        'currency'                => (string)$inv->currency,
                        'status'                  => 'paid',
                        'paid_at'                 => date('Y-m-d H:i:s'),
                    ]);
                }
                break;

            default:
                // 他イベントは何もしない
                break;
        }

        return $this->response->withStringBody('ok');
    }

    /**
     * 請求履歴
     */
    public function history()
    {
        $auth = $this->Authentication->getIdentity();
        if (!$auth) {
            return $this->redirect('/employer/login');
        }

        $Invoices = $this->fetchTable('CompanyInvoices');
        $q = $Invoices->find()
            ->where(['company_id' => (int)$auth->id])
            ->order(['created' => 'DESC']);

        $this->paginate = ['limit' => 20];
        $invoices = $this->paginate($q);

        $this->set(compact('invoices'));
    }

    /**
     * Stripe の return_url から戻る成功ページ
     *  - Webhook が最終確定だが、保険として PI を検証して即反映も可能
     */
    public function success()
    {
        $this->request->allowMethod(['get']);

        $piId = (string)$this->request->getQuery('payment_intent');
        if (!$piId) {
            $this->Flash->error('決済情報が見つかりません。');
            return $this->redirect(['action' => 'plan']);
        }

        $cfg    = (array)Configure::read('Stripe');
        $secret = $cfg['secret'] ?? null;
        if (!$secret) {
            $this->Flash->error('Stripe シークレットキーが未設定です。');
            return $this->redirect(['action' => 'plan']);
        }

        $stripe = new StripeClient($secret);

        try {
            $pi = $stripe->paymentIntents->retrieve($piId, []);
        } catch (\Throwable $e) {
            $this->Flash->error('決済の確認に失敗しました。');
            return $this->redirect(['action' => 'plan']);
        }

        if (($pi->status ?? '') !== 'succeeded') {
            $this->Flash->error('決済が完了していません。（status=' . ($pi->status ?? 'unknown') . '）');
            return $this->redirect(['action' => 'plan']);
        }

        $companyId  = (int)($pi->metadata->company_id  ?? 0);
        $targetPlan = (string)($pi->metadata->target_plan ?? '');

        $auth = $this->Authentication->getIdentity();
        if (!$auth || (int)$auth->id !== $companyId || $targetPlan === '') {
            $this->Flash->error('不正なアクセスです。');
            return $this->redirect(['action' => 'plan']);
        }

        // 顧客整合性チェック（任意）
        $Companies = $this->fetchTable('Companies');
        $company   = $Companies->get($companyId);
        if (!empty($company->stripe_customer_id) && !empty($pi->customer)
            && $company->stripe_customer_id !== $pi->customer) {
            $this->Flash->error('決済情報と会社情報が一致しません。');
            return $this->redirect(['action' => 'plan']);
        }

        // 反映（Webhookが来るまでの保険）
        $company->plan = $targetPlan;
        if ($Companies->save($company)) {
            $this->Authentication->setIdentity($Companies->get($companyId));
            $this->Flash->success('決済が完了しました。プランを「' . h($targetPlan) . '」に更新しました。');
            return $this->redirect('/employer/companies/view/' . $companyId);
        }

        $this->Flash->error('プランの更新に失敗しました。');
        return $this->redirect(['action' => 'plan']);
    }

    public function cancel()
    {
        $this->Flash->error('決済をキャンセルしました。');
        return $this->redirect(['action' => 'plan']);
    }
}
