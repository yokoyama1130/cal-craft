<?php
declare(strict_types=1);

namespace App\Controller\Employer;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Stripe\StripeClient;

class BillingController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        // Employer認証必須（= identity は Company）
        $this->Authentication->addUnauthenticatedActions([]);
        $this->viewBuilder()->setTemplatePath('Employer/Billing');
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

    public function success()
    {
        $this->Flash->success('決済が完了（反映には数秒〜数分かかることがあります）');
        return $this->redirect(['action' => 'plan']);
    }

    public function cancel()
    {
        $this->Flash->error('決済をキャンセルしました。');
        return $this->redirect(['action' => 'plan']);
    }
}
