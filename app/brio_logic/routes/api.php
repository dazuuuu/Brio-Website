<?php
// JSON API. Public catalogue endpoints + admin endpoints (session + CSRF protected).
use Brio\Http\Controllers\Api\HeroApiController;
use Brio\Http\Controllers\Api\LookupApiController;
use Brio\Http\Controllers\Api\NavApiController;
use Brio\Http\Controllers\Api\OrderApiController;
use Brio\Http\Controllers\Api\PageApiController;
use Brio\Http\Controllers\Api\ProductApiController;
use Brio\Http\Controllers\Api\ResetApiController;
use Brio\Http\Controllers\Api\SettingsApiController;
use Brio\Http\Middleware\Middleware as M;
use Brio\Http\Request;
use Brio\Http\Router;

return static function (Router $router): void {
    $c = static fn(string $method) => static fn(Request $r) => (new ProductApiController(brio('products')))->$method($r);
    $admin = [M::installed(), M::admin(), M::csrf()];

    $router->get('api/products', $c('index'), [M::installed()]);
    $router->get('api/products/{id}', $c('show'), [M::installed()]);

    $router->get('api/admin/stats', $c('stats'), $admin);
    $router->get('api/admin/products', $c('adminIndex'), $admin);
    $router->get('api/admin/products/{id}', $c('show'), $admin);
    $router->post('api/admin/products', $c('store'), $admin);
    $router->post('api/admin/products/{id}', $c('update'), $admin);
    $router->patch('api/admin/products/{id}', $c('update'), $admin);
    $router->delete('api/admin/products/{id}', $c('destroy'), $admin);

    // Public checkout
    $order = static fn(string $m) => static fn(Request $r) => (new OrderApiController())->$m($r);
    $router->post('api/orders', $order('place'), [M::installed(), M::sameOrigin()]);

    // Admin: everything below needs a signed-in admin and a CSRF token
    $make = static fn(string $class, string $m) => static fn(Request $r) => (new $class())->$m($r);

    $router->get('api/admin/link-options', $make(LookupApiController::class, 'linkOptions'), $admin);
    $router->post('api/admin/uploads', $make(LookupApiController::class, 'upload'), $admin);

    $router->get('api/admin/hero', $make(HeroApiController::class, 'index'), $admin);
    $router->post('api/admin/hero/reorder', $make(HeroApiController::class, 'reorder'), $admin);
    $router->post('api/admin/hero', $make(HeroApiController::class, 'store'), $admin);
    $router->post('api/admin/hero/{id}', $make(HeroApiController::class, 'update'), $admin);
    $router->patch('api/admin/hero/{id}', $make(HeroApiController::class, 'update'), $admin);
    $router->delete('api/admin/hero/{id}', $make(HeroApiController::class, 'destroy'), $admin);

    $router->get('api/admin/pages', $make(PageApiController::class, 'index'), $admin);
    $router->get('api/admin/pages/{id}', $make(PageApiController::class, 'show'), $admin);
    $router->post('api/admin/pages/restore', $make(PageApiController::class, 'restore'), $admin);
    $router->post('api/admin/pages', $make(PageApiController::class, 'store'), $admin);
    $router->patch('api/admin/pages/{id}', $make(PageApiController::class, 'update'), $admin);
    $router->delete('api/admin/pages/{id}', $make(PageApiController::class, 'destroy'), $admin);

    $router->get('api/admin/nav', $make(NavApiController::class, 'index'), $admin);
    $router->post('api/admin/nav', $make(NavApiController::class, 'save'), $admin);

    $router->get('api/admin/orders', $make(OrderApiController::class, 'index'), $admin);
    $router->patch('api/admin/orders/{id}', $make(OrderApiController::class, 'update'), $admin);
    $router->delete('api/admin/orders/{id}', $make(OrderApiController::class, 'destroy'), $admin);

    $router->post('api/admin/reset', $make(ResetApiController::class, 'run'), $admin);

    $router->get('api/admin/settings', $make(SettingsApiController::class, 'show'), $admin);
    $router->post('api/admin/settings', $make(SettingsApiController::class, 'save'), $admin);
};
