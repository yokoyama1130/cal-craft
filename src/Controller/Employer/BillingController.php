<?php
declare(strict_types=1);

namespace App\Controller\Employer;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Stripe\StripeClient;

class BillingController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->addUnauthenticatedActions([]);
        $this->viewBuilder()->setTemplatePath('Employer/Billing');
    }

    // CSRFの影響を避けたいAPIは除外
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        // FormProtection/Csrf を使っている場合は intent/webhook を除外
        if (property_exists($this, 'FormProtection')) {
            $this->FormProtection->setConfig('unlockedActions', ['intent']);
        }
    }

    public function plan()
    {
        $auth = $this->Authentication->getIdentity();
        if (!$auth) return $this->redirect('/employer/login');

        // 最新を取り直し（Identity の古さ対策）
        $Companies = $this->fetchTable('Companies');
        $company   = $Companies->get($auth->id);

        $plans = [
            'free' => [
                'label'=>'Free', 'price'=>0,
                'features'=>['基本機能','当月 1ユーザーに先出しメッセージ'],
            ],
            'pro'  => [
                'label'=>'Pro', 'price'=>9800,
                'features'=>['高度機能','当月 100ユーザーに先出しメッセージ'],
            ],
            'enterprise' => [
                'label'=>'Enterprise', 'price'=>null,
                'features'=>['無制限','SLA/請求書対応'],
            ],
        ];

        $this->set(compact('company','plans'));
    }

    // /employer/billing/checkout/{planKey}
    public function checkout(string $planKey)
    {
        $auth = $this->Authentication->getIdentity();
        if (!$auth) return $this->redirect('/employer/login');

        $valid = ['free','pro','enterprise'];
        if (!in_array($planKey, $valid, true)) {
            throw new BadRequestException('Invalid plan.');
        }

        $Companies = $this->fetchTable('Companies');
        $company   = $Companies->get($auth->id);

        // Free は即時反映（ここは課金ナシ）
        if ($planKey === 'free') {
            $company->plan = 'free';
            $company->stripe_subscription_id = null;
            if ($Companies->save($company)) {
                $this->Authentication->setIdentity($Companies->get($auth->id));
                $this->Flash->success('Freeプランに変更しました。');
            } else {
                $this->Flash->error('プラン変更に失敗しました。');
            }
            return $this->redirect('/employer/billing/plan');
        }

        // ここから有料（Stripe Checkout）
        $cfg        = (array)Configure::read('Stripe');
        $secret     = $cfg['secret']      ?? null;
        $priceMap   = $cfg['price_map']   ?? [];
        $successUrl = $cfg['success_url'] ?? null;
        $cancelUrl  = $cfg['cancel_url']  ?? null;

        $priceId = $priceMap[$planKey] ?? null;
        if (!$secret || !$priceId || !$successUrl || !$cancelUrl) {
            // 設定が足りない場合は開発モード：即時反映（回避策）
            $company->plan = $planKey;
            if ($Companies->save($company)) {
                $this->Authentication->setIdentity($Companies->get($auth->id));
                $this->Flash->success("（開発モード）プランを {$planKey} に変更しました。");
            } else {
                $this->Flash->error('プラン変更に失敗しました。');
            }
            return $this->redirect('/employer/billing/plan');
        }

        $stripe = new StripeClient($secret);

        // Customer を作成/再利用
        $customerId = $company->stripe_customer_id ?: null;
        if (!$customerId) {
            $customer = $stripe->customers->create([
                'name'  => (string)$company->name,
                'email' => $company->auth_email ?: null,
                'metadata' => ['company_id' => (string)$company->id],
            ]);
            $customerId = $customer->id;

            $company->stripe_customer_id = $customerId;
            $Companies->save($company);
        }

        // Checkout Session（定期課金）
        $session = $stripe->checkout->sessions->create([
            'mode'        => 'subscription',
            'customer'    => $customerId,
            'line_items'  => [[ 'price' => $priceId, 'quantity' => 1 ]],
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
            'metadata'    => [
                'company_id' => (string)$company->id,
                'target_plan'=> $planKey,
            ],
        ]);

        return $this->redirect($session->url);
    }

    /**
     * カード入力画面（Payment Element）
     * /employer/billing/pay/{planKey}
     */
    public function pay(string $planKey)
    {
        $auth = $this->Authentication->getIdentity();
        if (!$auth) return $this->redirect('/employer/login');

        $valid = ['pro','enterprise'];
        if (!in_array($planKey, $valid, true)) {
            throw new BadRequestException('Invalid plan.');
        }

        // publishable key をビューへ
        $pk = (string)(Configure::read('Stripe.publishable') ?? '');
        if (!$pk) {
            $this->Flash->error('Stripeの公開鍵が未設定です。');
            return $this->redirect(['action'=>'plan']);
        }

        $this->set([
            'planKey' => $planKey,
            'publishableKey' => $pk,
        ]);
    }

    /**
     * クライアントへ PaymentIntent（最新請求書のもの）の client_secret を返すAPI
     * /employer/billing/intent/{planKey}  [POST]
     *
     * - サブスクを incomplete で仮作成し、latest_invoice.payment_intent の client_secret を返す
     * - フロントは confirmCardPayment で確定
     */
    public function intent(string $planKey)
    {
        $this->request->allowMethod(['post']);
        $company = $this->Authentication->getIdentity();

        // 金額マップ（例）
        $amountMap = ['pro' => 9800, 'enterprise' => 20000]; // 円
        $amount = $amountMap[$planKey] ?? null;
        if (!$amount) {
            return $this->response->withStatus(400);
        }

        $cfg = \Cake\Core\Configure::read('Stripe');
        if (empty($cfg['secret'])) {
            return $this->response->withStatus(500);
        }
        $stripe = new \Stripe\StripeClient($cfg['secret']);

        // 顧客（あるなら再利用）
        $customerId = $company->stripe_customer_id ?? null;
        if (!$customerId) {
            $c = $stripe->customers->create([
                'name' => $company->name,
                'email'=> $company->auth_email ?: null,
                'metadata' => ['company_id' => (string)$company->id],
            ]);
            $customerId = $c->id;
            $Companies = $this->fetchTable('Companies');
            $rec = $Companies->get($company->id);
            $rec->stripe_customer_id = $customerId;
            $Companies->save($rec);
        }

        // JPY, 金額は「整数（例：9800 = ¥9,800）」
        $pi = $stripe->paymentIntents->create([
            'amount' => $amount,
            'currency' => 'jpy',
            'customer' => $customerId,
            // 3Dセキュア対応
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'company_id' => (string)$company->id,
                'target_plan'=> $planKey,
                'type' => 'one_time_upgrade',
            ],
        ]);

        $this->response = $this->response->withType('application/json');
        return $this->response->withStringBody(json_encode(['clientSecret' => $pi->client_secret]));
    }

    /**
     * Webhook エンドポイント（Stripe -> あなたのサーバー）
     * - ここは CSRF をスキップし、認証も不要
     * - Application の CsrfProtectionMiddleware で skip する設定も下に例を記載
     */
    public function webhook()
    {
        $this->request->allowMethod(['post']);

        $secret = (string)Configure::read('Stripe.secret');
        $whSecret = (string)Configure::read('Stripe.webhook_secret');
        if (!$secret || !$whSecret) {
            // 署名検証できないなら 400
            return $this->response->withStatus(400)->withStringBody('Stripe keys not configured');
        }

        $payload = (string)$this->request->getBody()->getContents();
        $sig = $this->request->getHeaderLine('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sig, $whSecret);
        } catch (\Throwable $e) {
            return $this->response->withStatus(400)->withStringBody('Invalid signature');
        }

        $Companies = $this->fetchTable('Companies');
        $Invoices  = $this->fetchTable('CompanyInvoices');

        // 共通：監査用にレコード保存する小ヘルパ
        $saveInvoice = function(array $data) use ($Invoices, $payload) {
            $rec = $Invoices->newEmptyEntity();
            $rec = $Invoices->patchEntity($rec, $data + ['raw_payload' => $payload]);
            $Invoices->save($rec);
            return $rec;
        };

        switch ($event->type) {
            /**
             * Payment Element での都度決済が成功
             */
            case 'payment_intent.succeeded':
            {
                /** @var \Stripe\PaymentIntent $pi */
                $pi = $event->data->object;

                $companyId  = (int)($pi->metadata['company_id'] ?? 0);
                $targetPlan = (string)($pi->metadata['target_plan'] ?? '');
                if ($companyId && $targetPlan) {
                    // プラン更新
                    $company = $Companies->get($companyId);
                    $company->plan = $targetPlan;
                    $Companies->save($company);

                    // 請求履歴を保存
                    $saveInvoice([
                        'company_id' => $companyId,
                        'stripe_customer_id' => (string)$pi->customer,
                        'stripe_payment_intent_id' => (string)$pi->id,
                        'plan' => $targetPlan,
                        'amount' => (int)$pi->amount,
                        'currency' => (string)$pi->currency,
                        'status' => 'paid',
                        'paid_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                break;
            }

            /**
             * （サブスク運用時用）請求書が支払われた
             */
            case 'invoice.payment_succeeded':
            {
                /** @var \Stripe\Invoice $inv */
                $inv = $event->data->object;

                // 顧客から会社を引く
                $company = $Companies->find()->where(['stripe_customer_id' => $inv->customer])->first();
                if ($company) {
                    $saveInvoice([
                        'company_id' => (int)$company->id,
                        'stripe_customer_id' => (string)$inv->customer,
                        'stripe_subscription_id' => (string)($inv->subscription ?? ''),
                        'stripe_invoice_id' => (string)$inv->id,
                        'plan' => (string)($inv->lines->data[0]->plan->nickname ?? ''), // 任意
                        'amount' => (int)$inv->amount_paid,
                        'currency' => (string)$inv->currency,
                        'status' => 'paid',
                        'paid_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                break;
            }

            default:
                // 他イベントは 200 で黙ってOK
                break;
        }

        return $this->response->withStatus(200)->withStringBody('ok');
    }

    /**
     * 請求履歴（会社側）
     */
    public function history()
    {
        $auth = $this->Authentication->getIdentity();
        if (!$auth) return $this->redirect('/employer/login');

        $Invoices = $this->fetchTable('CompanyInvoices');
        $q = $Invoices->find()
            ->where(['company_id' => (int)$auth->id])
            ->order(['created' => 'DESC']);

        $this->paginate = ['limit' => 20];
        $invoices = $this->paginate($q);

        $this->set(compact('invoices'));
    }

    public function success()
    {
        $this->request->allowMethod(['get']);
    
        // Stripe から付与されるクエリ
        $piId  = (string)$this->request->getQuery('payment_intent');
        $redir = (string)$this->request->getQuery('redirect_status'); // succeeded など
    
        if (!$piId) {
            $this->Flash->error('決済情報が見つかりません（payment_intent 不足）。');
            return $this->redirect(['action' => 'plan']);
        }
    
        // Stripe API で PaymentIntent を検証
        $cfg = (array)Configure::read('Stripe');
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
    
        // ステータス確認
        if (($pi->status ?? '') !== 'succeeded') {
            $this->Flash->error('決済が完了していません。（status=' . ($pi->status ?? 'unknown') . '）');
            return $this->redirect(['action' => 'plan']);
        }
    
        // メタデータから会社とプランを取得（intent 作成時に入れていた値）
        $companyId  = (int)($pi->metadata['company_id']  ?? 0);
        $targetPlan = (string)($pi->metadata['target_plan'] ?? '');
    
        if ($companyId <= 0 || $targetPlan === '') {
            $this->Flash->error('決済メタデータが不正です。');
            return $this->redirect(['action' => 'plan']);
        }
    
        // ログイン会社と整合を軽くチェック（任意）
        $auth = $this->Authentication->getIdentity();
        if (!$auth || (int)$auth->id !== $companyId) {
            $this->Flash->error('不正なアクセスです。');
            return $this->redirect(['action' => 'plan']);
        }
    
        // 顧客IDの整合性チェック（改ざん防止の一手）
        $Companies = $this->fetchTable('Companies');
        $company   = $Companies->get($companyId);
        if (!empty($company->stripe_customer_id) && !empty($pi->customer) && $company->stripe_customer_id !== $pi->customer) {
            $this->Flash->error('決済情報と会社情報が一致しません。');
            return $this->redirect(['action' => 'plan']);
        }
    
        // プラン更新
        $company->plan = $targetPlan;
        if ($Companies->save($company)) {
            // Identity を更新して画面の表示ズレを防止
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
