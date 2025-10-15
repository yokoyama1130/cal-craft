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
        // 既存: Stripe Webhook 判定
        $isStripeWebhook = function ($request): bool {
            $params = (array)$request->getAttribute('params');
            $path = strtolower($request->getUri()->getPath() ?? '');

            $isAltByParams =
                (strtolower((string)($params['controller'] ?? '')) === 'webhooks') &&
                (strtolower((string)($params['action'] ?? '')) === 'stripe');

            $isAltByPath =
                ($path === '/webhook/stripe') ||
                ($path === '/webhooks/stripe');

            $isEmployerWebhook =
                (strtolower((string)($params['prefix'] ?? '')) === 'employer' &&
                 strtolower((string)($params['controller'] ?? '')) === 'billing' &&
                 strtolower((string)($params['action'] ?? '')) === 'webhook')
                || str_starts_with($path, '/employer/billing/webhook');

            return $isAltByParams || $isAltByPath || $isEmployerWebhook;
        };

        // ★ 追加: API 判定（最小変更）
        $isApi = function ($request): bool {
            return strtolower((string)$request->getParam('prefix')) === 'api';
        };

        // CSRF（既存）+ API/Webhook を skip
        $csrf = new CsrfProtectionMiddleware([
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $csrf->skipCheckCallback(function ($request) use ($isStripeWebhook, $isApi) {
            return $isStripeWebhook($request) || $isApi($request);
        });

        return $q
            ->add(new ErrorHandlerMiddleware(Configure::read('Error'), $this))
            ->add(new AssetMiddleware(['cacheTime' => Configure::read('Asset.cacheTime')]))
            ->add(new RoutingMiddleware($this))
            ->add(new BodyParserMiddleware())
            // 認証ミドルウェアは Webhook と API をスキップ（APIでは未認証リダイレクトさせない）
            ->add(new AuthenticationMiddleware($this, [
                'skipCheckCallback' => function ($request) use ($isStripeWebhook, $isApi) {
                    return $isStripeWebhook($request) || $isApi($request);
                },
            ]))
            ->add($csrf);
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
                'resolver' => ['className' => 'Authentication.Orm', 'userModel' => 'Companies'],
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
        } elseif ($prefix === 'Api') {
            // ★ 追加: API 用（最小限）
            // Identifier は Users を対象（将来 /api/users/login で使い回せます）
            $service->loadIdentifier('Authentication.Password', [
                'fields' => ['username' => 'email', 'password' => 'password'],
                'resolver' => ['className' => 'Authentication.Orm', 'userModel' => 'Users'],
            ]);

            // ★ 重要: Authenticator を 1 つはロード（これが無いと "No authenticators loaded"）
            // APIでは基本的にセッションは使いませんが、コンポーネントが存在を要求するため軽量に Session を積んでおきます
            $service->loadAuthenticator('Authentication.Session');

            // APIは絶対にHTMLにリダイレクトさせない
            $service->setConfig([
                'unauthenticatedRedirect' => null,
                'queryParam' => null,
            ]);

            // （将来JWT等を使うならここで Token/Jwt authenticator を追加）
        } else {
            // 一般ユーザー（Web）
            $service->loadIdentifier('Authentication.Password', [
                'fields' => ['username' => 'email', 'password' => 'password'],
                'resolver' => ['className' => 'Authentication.Orm', 'userModel' => 'Users'],
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
