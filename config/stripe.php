<?php
return [
    'Stripe' => [
        // .env から読むのが安全
        'secret'        => env('STRIPE_SECRET', null),
        'publishable'   => env('STRIPE_PUBLISHABLE', null),
        // Stripe ダッシュボードで作成した Price ID に置き換え
        'price_map'     => [
            'pro'        => env('STRIPE_PRICE_PRO', null),
            'enterprise' => env('STRIPE_PRICE_ENTERPRISE', null),
        ],
        // Checkout 成功/失敗時の戻り先
        'success_url'   => env('STRIPE_SUCCESS_URL', 'http://localhost:8765/employer/billing/success'),
        'cancel_url'    => env('STRIPE_CANCEL_URL',  'http://localhost:8765/employer/billing/cancel'),

        // Webhook 検証用（ダッシュボードの「Signing secret」）
        'webhook_secret'=> env('STRIPE_WEBHOOK_SECRET', null),
    ],
];
