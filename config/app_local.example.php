<?php
/*
 * Local configuration file to provide any overrides to your app.php configuration.
 * Copy and save this file as app_local.php and make changes as required.
 * Note: It is not recommended to commit files with credentials such as app_local.php
 * into source code version control.
 */
return [
    /*
     * Debug Level:
     *
     * Production Mode:
     * false: No error messages, errors, or warnings shown.
     *
     * Development Mode:
     * true: Errors and warnings shown.
     */
    'debug' => filter_var(env('DEBUG', true), FILTER_VALIDATE_BOOLEAN),

    /*
     * Security and encryption configuration
     *
     * - salt - A random string used in security hashing methods.
     *   The salt value is also used as the encryption key.
     *   You should treat it as extremely sensitive data.
     */
    'Security' => [
        'salt' => env('SECURITY_SALT', '9dbad9e7194584ef919d740d3576e2248413aafc5da247e11c22613d6626846c'),
    ],

    /*
     * Connection information used by the ORM to connect
     * to your application's datastores.
     *
     * See app.php for more configuration options.
     */
    'Datasources' => [
        'default' => [
            'host' => 'db',
            'username' => 'cakephp',
            'password' => 'cakephp',
            'database' => 'link_app',
            'url' => env('DATABASE_URL', null),
        ],

        /*
         * The test connection is used during the test suite.
         */
        'test' => [
            'host' => 'db',
            //'port' => 'non_standard_port_number',
            'username' => 'my_app',
            'password' => 'secret',
            'database' => 'test_myapp',
            //'schema' => 'myapp',
            'url' => env('DATABASE_TEST_URL', 'sqlite://127.0.0.1/tmp/tests.sqlite'),
        ],
    ],

    /*
     * Email configuration.
     *
     * Host and credential configuration in case you are using SmtpTransport
     *
     * See app.php for more configuration options.
     * 
     * ここのメールアドレスを変えて、そのGmailからパスワード取得すれば送信先を変えられるんだと思う
     * 公式のメールアドレスを作ったらここ修正する
     */
    'EmailTransport' => [
        'default' => [
            'className' => 'Smtp',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => env('SMTP_USERNAME', ''),
            'password' => env('SMTP_PASSWORD', ''),
            'tls' => true,
        ],
    ],
    'Email' => [
        'default' => [
            'transport' => 'default',
            'from' => [env('SMTP_FROM', 'no-reply@your-domain.tld') => 'OrcaFolio'],
            'emailFormat' => 'text',
            'charset' => 'utf-8',
            'headerCharset' => 'utf-8',
        ],
    ],

    'Stripe' => [
        // ← ここを追加
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', 'pk_test_51S1k1sCgJxKS2UMCXMdmrELYuleNq9CPeBrdt8fHsY5YQL9azD0SNSc2ksWJzfT25P22f0WpAayWKWlvoj8zXHDE00LraoHPvf'),
        // secret → secret_key に変更（コントローラ側と揃える）
        'secret_key'      => env('STRIPE_SECRET_KEY', 'sk_test_51S1k1sCgJxKS2UMCu4ZaIM0WiCeYmKfx8IJtXzywNcNSaEKpk6CfMwrw6lKLIbrlwmAMgLSDoanOfiRhraEmxC7n00mUCG10Az'),

        // 価格ID（Stripeダッシュボードで作った “月額” Price のID）
        'price_map' => [
            'pro'        => env('STRIPE_PRICE_PRO'),
            'enterprise' => env('STRIPE_PRICE_ENT'),
        ],

        // 成功/キャンセルURL（{CHECKOUT_SESSION_ID} を含める）
        'success_url' => 'http://localhost:8765/employer/billing/success?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => 'http://localhost:8765/employer/billing/cancel',

        // Webhookシークレット（ダッシュボードで /webhooks/stripe を登録して得た whsec_...）
        'webhook_secret'=> 'whsec_06b679dd1e4597a7d52cf47cb824c51b3a2974001ddca40860e0c2d4ca2d43fc',
    ],
];
