<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;

class StripeController extends AppController
{
    // CSRF/フォーム保護の影響を避けるならこのアクションだけ除外設定でもOK
    public function webhook()
    {
        $this->request->allowMethod(['post']);

        $payload    = (string)$this->request->getBody();
        $sigHeader  = $this->request->getHeaderLine('Stripe-Signature');
        $whSecret   = (string)(Configure::read('Stripe.webhook_secret') ?? '');

        if (!$whSecret) {
            // 開発中：署名検証なし（本番は必ず検証）
            $event = json_decode($payload, true);
        } else {
            try {
                $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $whSecret);
                $event = $event->toArray();
            } catch (\Throwable $e) {
                return $this->response->withStatus(400);
            }
        }

        $type = $event['type'] ?? '';

        // checkout 完了 → サブスクID/プランを反映
        if ($type === 'checkout.session.completed') {
            $session = $event['data']['object'] ?? [];
            $companyId = (int)($session['metadata']['company_id'] ?? 0);
            $target    = (string)($session['metadata']['target_plan'] ?? '');

            if ($companyId && in_array($target, ['pro','enterprise'], true)) {
                $Companies = $this->fetchTable('Companies');
                $c = $Companies->get($companyId);

                // subscription は session 内の subscription フィールドに入る
                $c->stripe_subscription_id = $session['subscription'] ?? null;
                $c->plan = $target;
                $Companies->save($c);
            }
        }

        // 200 OK
        return $this->response->withStringBody('ok');
    }
}
