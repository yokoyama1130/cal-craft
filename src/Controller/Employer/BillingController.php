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
        $auth = $this->Authentication->getIdentity();
        if (!$auth) return $this->redirect('/employer/login');
    
        // ★ ここで最新をDBから取り直す
        $Companies = $this->fetchTable('Companies');
        $company   = $Companies->get($auth->id);
    
        $plans = [
            'free' => ['label'=>'Free','price'=>0,'features'=>['月1ユーザーに先出しメッセージ','基本機能']],
            'pro'  => ['label'=>'Pro','price'=>2980,'features'=>['月100ユーザーに先出しメッセージ','高度検索','優先サポート']],
            'enterprise' => ['label'=>'Enterprise','price'=>0,'features'=>['無制限','SLA/管理機能','個別サポート']],
        ];
    
        $this->set(compact('company','plans'));
    }    

    // プラン確認/決済（まずは決済なし版 → 直接反映 or 次回更新に予約）
    public function checkout(?string $plan = null)
    {
        $auth = $this->Authentication->getIdentity();
        if (!$auth) {
            return $this->redirect('/employer/login');
        }
    
        $valid = ['free','pro','enterprise'];
        if (!$plan || !in_array($plan, $valid, true)) {
            throw new \Cake\Http\Exception\BadRequestException('Invalid plan.');
        }
    
        $Companies = $this->fetchTable('Companies');
        // ★ ここで会社を取得してビューへ渡す
        $company   = $Companies->get($auth->id);
    
        if ($this->request->is('post')) {
            $company->plan = $plan;
    
            if ($Companies->save($company)) {
                // ついでに Identity も更新（現在プラン表示のズレ防止）
                $this->Authentication->setIdentity($Companies->get($auth->id));
                $this->Flash->success('プランを変更しました。');
                return $this->redirect('/employer/companies/view/' . $auth->id);
            }
            $this->Flash->error('プラン変更に失敗しました。');
        }
    
        // ★ ビューで使う変数を渡す
        $this->set(compact('company', 'plan'));
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
