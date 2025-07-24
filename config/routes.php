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
};
