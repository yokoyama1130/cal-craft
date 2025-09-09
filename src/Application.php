<?php
declare(strict_types=1);

namespace App;

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
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
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
        // 共通の判定クロージャ（認証/CSRF の両方で使う）
        $isStripeWebhook = function ($request): bool {
            $p = (array)$request->getAttribute('params');
            $path = strtolower($request->getUri()->getPath() ?? '');

            $isEmployerWebhook =
                ((($p['prefix'] ?? '') === 'Employer') &&
                 (($p['controller'] ?? '') === 'Billing') &&
                 (($p['action'] ?? '') === 'webhook'))
                || $path === '/employer/billing/webhook';

            $isAltStripe =
                (($p['controller'] ?? '') === 'Webhooks' && ($p['action'] ?? '') === 'stripe')
                || $path === '/webhooks/stripe';

            return $isEmployerWebhook || $isAltStripe;
        };

        // CSRF は “インスタンス生成 → skipCheckCallback 呼び出し” が必須
        $csrf = new CsrfProtectionMiddleware();
        $csrf->skipCheckCallback($isStripeWebhook);

        $q
            ->add(new ErrorHandlerMiddleware(Configure::read('Error'), $this))
            ->add(new AssetMiddleware(['cacheTime' => Configure::read('Asset.cacheTime')]))
            ->add(new RoutingMiddleware($this))        // params を解決
            ->add(new BodyParserMiddleware())          // JSON 等のボディ解析
            ->add(new AuthenticationMiddleware($this, [
                'skipCheckCallback' => $isStripeWebhook // 認証は配列で OK
            ]))
            ->add($csrf);                               // ← ここはインスタンスを add

        return $q;
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
                'fields'   => ['username' => 'auth_email', 'password' => 'auth_password'],
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
                'fields'   => ['username' => 'email', 'password' => 'password'],
            ]);

            $service->setConfig([
                'unauthenticatedRedirect' => '/users/login',
                'queryParam' => 'redirect',
            ]);
        }

        return $service;
    }
}
