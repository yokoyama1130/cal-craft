<?php
declare(strict_types=1);

namespace App\Controller\Employer;

use App\Controller\AppController;
use Cake\Http\Exception\BadRequestException;

class BillingController extends AppController
{
    // 会社ログイン必須
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->addUnauthenticatedActions([]); // 全アクション要ログイン(Employer)
        $this->viewBuilder()->setTemplatePath('Employer/Billing');
    }

    // プラン一覧
    public function plan()
    {
        $company = $this->Authentication->getIdentity(); // Companies の行が入っている想定
        if (!$company) return $this->redirect('/employer/login');

        // 表示用の定義（後でDBや設定ファイルに移せます）
        $plans = [
            'free' => [
                'label' => 'Free',
                'price' => 0,
                'features' => ['月1ユーザーに先出しメッセージ', '基本機能'],
            ],
            'pro' => [
                'label' => 'Pro',
                'price' => 2980,
                'features' => ['月100ユーザーに先出しメッセージ', '高度検索', '優先サポート'],
            ],
            'enterprise' => [
                'label' => 'Enterprise',
                'price' => 0, // 要見積もり
                'features' => ['無制限', 'SLA/管理機能', '個別サポート'],
            ],
        ];

        $this->set(compact('company', 'plans'));
    }

    // プラン確認/決済（まずは決済なし版 → 直接反映 or 次回更新に予約）
    public function checkout($plan = null)
    {
        $company = $this->Authentication->getIdentity();
        if (!$company) return $this->redirect('/employer/login');

        $valid = ['free','pro','enterprise'];
        if (!$plan || !in_array($plan, $valid, true)) {
            throw new BadRequestException('Invalid plan.');
        }

        // POSTで確定
        if ($this->request->is('post')) {
            $Companies = $this->fetchTable('Companies');

            // ここで本来は Stripe セッション作成→成功コールバックでプラン反映
            // まずは「即時反映（開発用）」にしておく
            $entity = $Companies->get($company->id);
            $entity->plan = $plan;

            if ($Companies->save($entity)) {
                $this->Flash->success('プランを変更しました。');
                return $this->redirect('/employer/companies/view/' . $company->id);
            }
            $this->Flash->error('プラン変更に失敗しました。');
        }

        // 確認画面表示
        $this->set(compact('company','plan'));
    }

    public function success()
    {
        $this->Flash->success('決済が完了しました。プランが有効になりました。');
        return $this->redirect(['action' => 'plan']);
    }

    public function cancel()
    {
        $this->Flash->error('決済がキャンセルされました。');
        return $this->redirect(['action' => 'plan']);
    }
}
