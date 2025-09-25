<?php
declare(strict_types=1);

namespace App;

use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Psr\Http\Message\ServerRequestInterface;

class Application extends BaseApplication implements AuthenticationServiceProviderInterface
{
    /**
     * アプリケーション全体の初期化処理。
     *
     * プラグインのロードや環境依存の設定を行う。
     *
     * @return void
     */
    public function bootstrap(): void
    {
        parent::bootstrap();

        $this->addPlugin('Authentication');

        if (PHP_SAPI === 'cli') {
            $this->bootstrapCli();
        } else {
            FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(false));
        }

        // [直し]debugだから情報漏洩の可能性あり。消したいけど、消すとブラウザでエラー文が見える🥹
        if (Configure::read('debug')) {
            $this->addPlugin('DebugKit');
        }
    }

    /**
     * アプリケーション全体のミドルウェアを設定する。
     *
     * - エラーハンドラ
     * - 静的アセット
     * - ルーティング
     * - JSON / form ボディパーサ
     * - 認証
     * - CSRF 保護（Webhook は除外）
     *
     * @param \Cake\Http\MiddlewareQueue $q ミドルウェアキュー
     * @return \Cake\Http\MiddlewareQueue ミドルウェアキュー
     */
    public function middleware(MiddlewareQueue $q): MiddlewareQueue
    {
        /**
         * Stripe Webhook を CSRF / 認証チェックから除外する共通判定
         * - /webhook/stripe（単数）
         * - /webhooks/stripe（複数：互換で残す）
         * - /employer/billing/webhook（Employer配下を使う場合）
         */
        $isStripeWebhook = function ($request): bool {
            $params = (array)$request->getAttribute('params');
            $path = strtolower($request->getUri()->getPath() ?? '');

            // ルーティング解決済みの controller/action でも拾う
            $isAltByParams = (
                (strtolower((string)($params['controller'] ?? '')) === 'webhooks') &&
                (strtolower((string)($params['action'] ?? '')) === 'stripe')
            );

            // パスでの直叩きも拾う（CLI の forward はここに該当）
            $isAltByPath =
                ($path === '/webhook/stripe') || // ★ CLI の既定（今回の本命）
                ($path === '/webhooks/stripe'); // 互換

            // Employer 側の別口を使う場合（前方一致でケア）
            $isEmployerWebhook =
                (strtolower((string)($params['prefix'] ?? '')) === 'employer' &&
                 strtolower((string)($params['controller'] ?? '')) === 'billing' &&
                 strtolower((string)($params['action'] ?? '')) === 'webhook')
                || str_starts_with($path, '/employer/billing/webhook');

            return $isAltByParams || $isAltByPath || $isEmployerWebhook;
        };

        // CSRF はインスタンス化してから skipCheckCallback を設定する
        $csrf = new CsrfProtectionMiddleware([
            'httponly' => true,
        ]);
        $csrf->skipCheckCallback($isStripeWebhook);

        return $q
            ->add(new ErrorHandlerMiddleware(Configure::read('Error'), $this))
            ->add(new AssetMiddleware(['cacheTime' => Configure::read('Asset.cacheTime')]))
            ->add(new RoutingMiddleware($this)) // params を解決
            ->add(new BodyParserMiddleware()) // JSON / x-www-form-urlencoded などを解析
            ->add(new AuthenticationMiddleware($this, [
                'skipCheckCallback' => $isStripeWebhook, // ★ Webhook は認証スキップ
            ]))
            ->add($csrf); // ★ Webhook は CSRF スキップ
    }

    /**
     * サービスコンテナの定義を行う。
     *
     * ここでインターフェイスと具体クラスのバインディングを登録できる。
     * 現状は特に追加サービスはなし。
     *
     * @param \Cake\Core\ContainerInterface $container サービスコンテナ
     * @return void
     */
    public function services(ContainerInterface $container): void
    {
    }

    /**
     * CLI 実行時のブートストラップ処理。
     *
     * - Bake プラグインをオプションでロード
     * - Migrations プラグインをロード
     *
     * @return void
     */
    protected function bootstrapCli(): void
    {
        $this->addOptionalPlugin('Bake');
        $this->addPlugin('Migrations');
    }

    /**
     * 認証サービスを生成して返す。
     *
     * prefix に応じて Employer 用／一般ユーザー用の認証処理を切り替える。
     * - Employer: Companies テーブルを利用し、auth_email/auth_password を使用
     * - Users: Users テーブルを利用し、email/password を使用
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request リクエスト
     * @return \Authentication\AuthenticationServiceInterface 認証サービス
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $service = new AuthenticationService();
        $params = (array)$request->getAttribute('params');
        $prefix = $params['prefix'] ?? null;

        if ($prefix === 'Employer') {
            $service->loadIdentifier('Authentication.Password', [
                'fields' => ['username' => 'auth_email', 'password' => 'auth_password'],
                'resolver' => [
                    'className' => 'Authentication.Orm',
                    'userModel' => 'Companies',
                ],
            ]);

            $service->loadAuthenticator('Authentication.Session');
            $service->loadAuthenticator('Authentication.Form', [
                'loginUrl' => '/employer/login',
                'fields' => ['username' => 'auth_email', 'password' => 'auth_password'],
            ]);

            $service->setConfig([
                'unauthenticatedRedirect' => '/employer/login',
                'queryParam' => 'redirect',
            ]);
        } else {
            $service->loadIdentifier('Authentication.Password', [
                'fields' => ['username' => 'email', 'password' => 'password'],
                'resolver' => [
                    'className' => 'Authentication.Orm',
                    'userModel' => 'Users',
                ],
            ]);

            $service->loadAuthenticator('Authentication.Session');
            $service->loadAuthenticator('Authentication.Form', [
                'loginUrl' => '/users/login',
                'fields' => ['username' => 'email', 'password' => 'password'],
            ]);

            $service->setConfig([
                'unauthenticatedRedirect' => '/users/login',
                'queryParam' => 'redirect',
            ]);
        }

        return $service;
    }
}
