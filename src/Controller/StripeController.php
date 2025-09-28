<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Core\Configure;

class StripeController extends AppController
{
    /**
     * Stripe Webhook受信処理
     *
     * - Stripe から送信される Webhook イベントを受け取り処理する。
     * - 開発環境では署名検証を省略できるが、本番環境では必ず `Stripe-Signature` ヘッダーを検証。
     * - checkout.session.completed イベントを検知した場合、対象の会社レコードに
     *   サブスクリプションIDとプランを反映する。
     *
     * @return \Cake\Http\Response Stripe へのレスポンス（200 OK）
     */
    public function webhook()
    {
        $this->request->allowMethod(['post']);

        $payload = (string)$this->request->getBody();
        $sigHeader = $this->request->getHeaderLine('Stripe-Signature');
        $whSecret = (string)(Configure::read('Stripe.webhook_secret') ?? '');

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
            $target = (string)($session['metadata']['target_plan'] ?? '');

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
