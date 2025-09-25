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

        $csrf = new CsrfProtectionMiddleware([
            'httponly' => true,
        ]);

        $csrf->skipCheckCallback(function ($request) {
            return $request->getParam('controller') === 'Employer/Billing'
                && $request->getParam('action') === 'webhook';
        });

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

    public function services(ContainerInterface $container): void
    {
    }

    protected function bootstrapCli(): void
    {
        $this->addOptionalPlugin('Bake');
        $this->addPlugin('Migrations');
    }

    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $service = new AuthenticationService();
        $prefix = $request->getParam('prefix');

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
