<?php
/**
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes): void {
    $routes->connect('/', ['controller' => 'Home', 'action' => 'index']);  // ← トップをHome/indexに

    $routes->setRouteClass(DashedRoute::class);
    $routes->connect('/likes/toggle', ['controller' => 'Likes', 'action' => 'toggle', '_method' => 'POST']);
    $routes->connect('/notifications', ['controller' => 'Notifications', 'action' => 'index']);
    // config/routes.php
    $routes->connect('/follows/follow/:id', ['controller' => 'Follows', 'action' => 'follow'], ['pass' => ['id']]);
    $routes->connect('/follows/unfollow/:id', ['controller' => 'Follows', 'action' => 'unfollow'], ['pass' => ['id']]);
    $routes->connect('/users/:id/followings', ['controller' => 'Users', 'action' => 'followings'])->setPass(['id']);
    $routes->connect('/users/:id/followers', ['controller' => 'Users', 'action' => 'followers'])->setPass(['id']);
    $routes->connect('/favorites', ['controller' => 'Likes', 'action' => 'favorites']);
    $routes->prefix('admin', function (RouteBuilder $routes) {
        $routes->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);
        $routes->fallbacks(DashedRoute::class);
    });    
    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Top', 'action' => 'index']);
        $builder->connect('/pages/*', 'Pages::display');
        $builder->fallbacks();
    });
    $routes->prefix('admin', function (RouteBuilder $routes) {
        $routes->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);
        $routes->fallbacks(DashedRoute::class);
    });
    
    // config/routes.php
    $routes->connect('/settings', ['controller' => 'Settings', 'action' => 'index']);

    $routes->get('/settings/email', ['controller' => 'Settings', 'action' => 'editEmail']);
    $routes->post('/settings/email', ['controller' => 'Settings', 'action' => 'updateEmail']);

    $routes->get('/settings/password', ['controller' => 'Settings', 'action' => 'editPassword']);
    $routes->post('/settings/password', ['controller' => 'Settings', 'action' => 'updatePassword']);

    $routes->get('/settings/email/confirm/*', ['controller' => 'Settings', 'action' => 'confirmEmail']);

    $routes->get('/settings/delete', ['controller' => 'Settings', 'action' => 'deleteConfirm']);
    $routes->post('/settings/delete', ['controller' => 'Settings', 'action' => 'deleteAccount']);

    $routes->prefix('Employer', function ($routes) {
        $routes->connect('/login', ['controller' => 'Auth', 'action' => 'login']);
        $routes->connect('/logout', ['controller' => 'Auth', 'action' => 'logout']);
        $routes->connect('/portfolios', ['controller' => 'Portfolios', 'action' => 'index', 'prefix' => 'Employer']);
        $routes->connect('/portfolios/add', ['controller' => 'Portfolios', 'action' => 'add', 'prefix' => 'Employer']);
        $routes->connect('/billing/plan',     ['controller'=>'Billing','action'=>'plan']);
        $routes->connect('/billing/pay/:plan',    ['controller' => 'Billing', 'action' => 'pay'])
            ->setPass(['plan'])->setPatterns(['plan' => '[a-z]+']);
        $routes->connect('/billing/intent/:plan', ['controller' => 'Billing', 'action' => 'intent'])
            ->setPass(['plan'])->setPatterns(['plan' => '[a-z]+']);
        $routes->connect('/billing/history', ['controller' => 'Billing', 'action' => 'history']);
        $routes->connect('/billing/webhook', ['controller' => 'Billing', 'action' => 'webhook']);
        $routes->connect('/billing/success',  ['controller'=>'Billing','action'=>'success']);
        $routes->connect('/billing/cancel',   ['controller'=>'Billing','action'=>'cancel']);   // キャンセル
        $routes->fallbacks();
    });
    $routes->connect('/stripe/webhook', ['controller' => 'Stripe', 'action' => 'webhook', 'prefix' => false]);
    $routes->connect('/webhooks/stripe', ['controller' => 'Webhooks', 'action' => 'stripe', 'prefix' => false]);

    $routes->scope('/', function (\Cake\Routing\RouteBuilder $routes) {
        // /conversations/start/user/2 /conversations/start/company/4
        $routes->connect(
            '/conversations/start/:type/:id',
            ['controller' => 'Conversations', 'action' => 'start'],
            ['pass' => ['type','id'], 'id' => '\d+', 'type' => 'user|company']
        );
    
        // 後方互換: /conversations/start/2 → start(2)
        $routes->connect(
            '/conversations/start/:id',
            ['controller' => 'Conversations', 'action' => 'start'],
            ['pass' => ['id'], 'id' => '\d+']
        );
    });    
};
