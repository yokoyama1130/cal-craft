<?php
/**
 * @copyright     Copyright (c) Cake Software Foundation
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes): void {
    // ルートクラス
    $routes->setRouteClass(DashedRoute::class);

    // トップ（Home/index）
    $routes->connect('/', ['controller' => 'Home', 'action' => 'index']);

    // Likes（★ HTTPメソッド制約は第3引数）
    $routes->connect(
        '/likes/toggle',
        ['controller' => 'Likes', 'action' => 'toggle'],
        ['_method' => 'POST']
    );

    // 通知
    $routes->connect('/notifications', ['controller' => 'Notifications', 'action' => 'index']);

    // Follows
    $routes->connect(
        '/follows/follow/:id',
        ['controller' => 'Follows', 'action' => 'follow'],
        ['pass' => ['id']]
    );
    $routes->connect(
        '/follows/unfollow/:id',
        ['controller' => 'Follows', 'action' => 'unfollow'],
        ['pass' => ['id']]
    );

    // Users follow lists
    $routes->connect('/users/:id/followings', ['controller' => 'Users', 'action' => 'followings'])->setPass(['id']);
    $routes->connect('/users/:id/followers', ['controller' => 'Users', 'action' => 'followers'])->setPass(['id']);

    // お気に入り
    $routes->connect('/favorites', ['controller' => 'Likes', 'action' => 'favorites']);

    // Admin prefix
    $routes->prefix('Admin', function (RouteBuilder $routes) {
        $routes->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);
        $routes->fallbacks(DashedRoute::class);
    });

    // Pages + fallbacks
    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->connect('/pages/*', 'Pages::display');
        $builder->fallbacks();
    });

    // Settings
    $routes->connect('/settings', ['controller' => 'Settings', 'action' => 'index']);
    $routes->get('/settings/email', ['controller' => 'Settings', 'action' => 'editEmail']);
    $routes->post('/settings/email', ['controller' => 'Settings', 'action' => 'updateEmail']);
    $routes->get('/settings/password', ['controller' => 'Settings', 'action' => 'editPassword']);
    $routes->post('/settings/password', ['controller' => 'Settings', 'action' => 'updatePassword']);
    $routes->get('/settings/email/confirm/*', ['controller' => 'Settings', 'action' => 'confirmEmail']);
    $routes->get('/settings/delete', ['controller' => 'Settings', 'action' => 'deleteConfirm']);
    $routes->post('/settings/delete', ['controller' => 'Settings', 'action' => 'deleteAccount']);

    $routes->prefix('Api', function (RouteBuilder $routes) {
        // /api/... で .json を受け付ける
        $routes->setExtensions(['json']);

        // Users
        $routes->post('/users/login', ['controller' => 'Users', 'action' => 'login']);
        $routes->post('/users/register', ['controller' => 'Users', 'action' => 'register']);

        $routes->get('/users/profile', ['controller' => 'Users', 'action' => 'profile']);
        $routes->get('/users/view/:id', ['controller' => 'Users', 'action' => 'view'])
            ->setPass(['id'])->setPatterns(['id' => '\d+']);

        $routes->get('/users/:id/followers', ['controller' => 'Users', 'action' => 'followers'])
            ->setPass(['id'])->setPatterns(['id' => '\d+']);
        $routes->get('/users/:id/followings', ['controller' => 'Users', 'action' => 'followings'])
            ->setPass(['id'])->setPatterns(['id' => '\d+']);

        // Portfolios
        // POST /api/portfolios/add(.json)
        $routes->post('/portfolios/add', ['controller' => 'Portfolios', 'action' => 'add']);
        $routes->post('/portfolios/view', ['controller' => 'Portfolios', 'action' => 'view']);

        // 必要なら RESTful も可（/api/portfolios/:id など）
        // $routes->resources('Portfolios'); // ← add 独自なら無くてもOK

        $routes->fallbacks(DashedRoute::class);
    });

    // Employer prefix
    $routes->prefix('Employer', function (RouteBuilder $routes) {
        $routes->connect('/login', ['controller' => 'Auth', 'action' => 'login']);
        $routes->connect('/logout', ['controller' => 'Auth', 'action' => 'logout']);

        $routes->connect('/portfolios', ['controller' => 'Portfolios', 'action' => 'index']);
        $routes->connect('/portfolios/add', ['controller' => 'Portfolios', 'action' => 'add']);

        // Billing
        $routes->connect('/billing/plan', ['controller' => 'Billing', 'action' => 'plan']);
        $routes->connect('/billing/history', ['controller' => 'Billing', 'action' => 'history']);
        $routes->connect('/billing/success', ['controller' => 'Billing', 'action' => 'success']);

        // ★ Webhook（必ず第3引数で POST 制約）
        $routes->connect(
            '/billing/webhook',
            ['controller' => 'Billing', 'action' => 'webhook'],
            ['_method' => 'POST']
        );

        $routes->connect('/billing/cancel', ['controller' => 'Billing', 'action' => 'cancelAtPeriodEnd'], ['_method' => 'POST']);
        $routes->connect('/billing/cancel_now', ['controller' => 'Billing', 'action' => 'cancelNow'], ['_method' => 'POST']);
        $routes->connect('/billing/change/:plan', ['controller' => 'Billing', 'action' => 'changePlan'], ['pass' => ['plan'], '_method' => 'POST']);
        $routes->connect('/billing/checkout/:plan', ['controller' => 'Billing', 'action' => 'checkout'], ['pass' => ['plan'], '_method' => 'POST']);

        $routes->fallbacks(DashedRoute::class);

        // Settings
        $routes->connect('/settings', ['controller' => 'Settings', 'action' => 'index']);
        $routes->get('/settings/email', ['controller' => 'Settings', 'action' => 'editEmail']);
        $routes->post('/settings/email', ['controller' => 'Settings', 'action' => 'updateEmail']);
        $routes->get('/settings/password', ['controller' => 'Settings', 'action' => 'editPassword']);
        $routes->post('/settings/password', ['controller' => 'Settings', 'action' => 'updatePassword']);
        $routes->get('/settings/email/confirm/*', ['controller' => 'Settings', 'action' => 'confirmEmail']);
        $routes->get('/settings/delete', ['controller' => 'Settings', 'action' => 'deleteConfirm']);
        $routes->post('/settings/delete', ['controller' => 'Settings', 'action' => 'deleteAccount']);

        $routes->fallbacks(\Cake\Routing\Route\DashedRoute::class);
    });

    // Public webhooks
    // ─ CLI の forward 先（単数形）
    $routes->connect(
        '/webhook/stripe',
        ['controller' => 'Webhooks', 'action' => 'stripe'],
        ['_method' => 'POST']
    );
    // ─ 互換の複数形（必要なければ消してOK）
    $routes->connect(
        '/webhooks/stripe',
        ['controller' => 'Webhooks', 'action' => 'stripe'],
        ['_method' => 'POST']
    );

    // Conversations
    $routes->scope('/', function (RouteBuilder $routes) {
        $routes->connect(
            '/conversations/start/:type/:id',
            ['controller' => 'Conversations', 'action' => 'start'],
            ['pass' => ['type', 'id'], 'id' => '\d+', 'type' => 'user|company']
        );
        // 後方互換
        $routes->connect(
            '/conversations/start/:id',
            ['controller' => 'Conversations', 'action' => 'start'],
            ['pass' => ['id'], 'id' => '\d+']
        );
    });
};
