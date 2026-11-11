<?php
// Server-rendered admin pages.
use Brio\Http\Controllers\Admin\AuthController;
use Brio\Http\Controllers\Admin\DashboardController;
use Brio\Http\Controllers\Admin\ManageController;
use Brio\Http\Controllers\Admin\SetupController;
use Brio\Http\Middleware\Middleware as M;
use Brio\Http\Request;
use Brio\Http\Response;
use Brio\Http\Router;

return static function (Router $router): void {
    $setup = new SetupController();
    $auth = new AuthController();
    $dash = new DashboardController();
    $manage = new ManageController();
    $guard = [M::installed(), M::admin()];

    $router->get('admin', static fn(Request $r) => Response::redirect(admin_url('products')), $guard);

    // Installer at /setup — locked (404) for good once an admin account exists.
    $router->get('setup', $setup->show(...), [M::setupOpen()]);
    $router->post('setup/database', $setup->database(...), [M::setupOpen(), M::csrf()]);
    $router->post('setup/admin', $setup->admin(...), [M::setupOpen(), M::csrf()]);

    $router->get('admin/login', $auth->show(...), [M::installed()]);
    $router->post('admin/login', $auth->login(...), [M::installed(), M::csrf()]);
    $router->post('admin/logout', $auth->logout(...), [M::csrf()]);

    $router->get('admin/products', $dash->products(...), $guard);
    $router->get('admin/offers', $dash->offers(...), $guard);
    $router->get('admin/featured', $dash->featured(...), $guard);

    $router->get('admin/hero', $manage->hero(...), $guard);
    $router->get('admin/pages', $manage->pages(...), $guard);
    $router->get('admin/pages/new', $manage->pageNew(...), $guard);
    $router->get('admin/pages/{id}', $manage->pageEdit(...), $guard);
    $router->get('admin/navigation', $manage->navigation(...), $guard);
    $router->get('admin/orders', $manage->orders(...), $guard);
    $router->get('admin/settings', $manage->settings(...), $guard);
    $router->get('admin/reset', $manage->reset(...), $guard);
};
