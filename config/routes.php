<?php
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes): void {
    // ルートのデフォルト形
    $routes->setRouteClass(DashedRoute::class);

    // === Top ===
    $routes->connect('/', ['controller' => 'Home', 'action' => 'index']); // ← トップはこれだけ

    // === Likes ===
    $routes->post('/likes/toggle', ['controller' => 'Likes', 'action' => 'toggle']);

    // === Notifications ===
    $routes->connect('/notifications', ['controller' => 'Notifications', 'action' => 'index']);

    // === Follows（POSTで縛る）===
    $routes->post('/follows/follow/:id', ['controller' => 'Follows', 'action' => 'follow'])
        ->setPass(['id'])->setPatterns(['id' => '\d+']);
    $routes->post('/follows/unfollow/:id', ['controller' => 'Follows', 'action' => 'unfollow'])
        ->setPass(['id'])->setPatterns(['id' => '\d+']);

    // 必要ならAjax用の別エンドポイントもここに

    // === Users ===
    $routes->connect('/users/:id/followings', ['controller' => 'Users', 'action' => 'followings'],
        ['pass' => ['id'], 'id' => '\d+']);
    $routes->connect('/users/:id/followers', ['controller' => 'Users', 'action' => 'followers'],
        ['pass' => ['id'], 'id' => '\d+']);

    // メール認証関連（URLを固定したい場合）
    $routes->connect('/users/verify-email/*', ['controller' => 'Users', 'action' => 'verifyEmail']);
    $routes->connect('/users/resend-verification', ['controller' => 'Users', 'action' => 'resendVerification']);

    // === Settings ===
    $routes->connect('/settings', ['controller' => 'Settings', 'action' => 'index']);
    $routes->scope('/settings', function (RouteBuilder $r): void {
        $r->get('/email', ['controller' => 'Settings', 'action' => 'editEmail']);
        $r->post('/email', ['controller' => 'Settings', 'action' => 'updateEmail']);
        $r->get('/email/confirm/*', ['controller' => 'Settings', 'action' => 'confirmEmail']);

        $r->get('/password', ['controller' => 'Settings', 'action' => 'editPassword']);
        $r->post('/password', ['controller' => 'Settings', 'action' => 'updatePassword']);

        $r->get('/delete', ['controller' => 'Settings', 'action' => 'deleteConfirm']);
        $r->post('/delete', ['controller' => 'Settings', 'action' => 'deleteAccount']);
    });

    // === Fallbacks（最後に1回だけ）===
    $routes->fallbacks(DashedRoute::class);

    // === Admin prefix（1回だけ）===
    $routes->prefix('admin', function (RouteBuilder $r): void {
        $r->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);
        $r->fallbacks(DashedRoute::class);
    });
};
