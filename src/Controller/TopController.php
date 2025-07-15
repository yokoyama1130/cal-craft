<?php
declare(strict_types=1);

namespace App\Controller;

class TopController extends AppController
{
    public function index()
    {
        $this->loadModel('Likes');
        $this->loadModel('Portfolios');

        $identity = $this->request->getAttribute('identity');
        $userId = $identity ? $identity->get('id') : null;

        $portfolios = $this->Portfolios->find()
            ->where(['is_public' => true])
            ->order(['created' => 'DESC'])
            ->limit(10)
            ->toArray(); // ← これ追加することで foreach で扱いやすくなる

        foreach ($portfolios as $p) {
            // いいね数
            $p->like_count = $this->Likes->find()
                ->where(['portfolio_id' => $p->id])
                ->count();

            // ✅ ここが抜けてた：ログイン中のユーザーがいいね済みか
            $p->liked_by_me = false;
            if ($userId !== null) {
                $p->liked_by_me = $this->Likes->exists([
                    'user_id' => $userId,
                    'portfolio_id' => $p->id,
                ]);
            }
        }

        $this->set(compact('portfolios'));
    }

}
