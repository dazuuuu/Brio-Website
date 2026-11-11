<?php
// Front controller: routes clean URLs to pages in /pages.
declare(strict_types=1);

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('Brio needs PHP 8.1 or newer. This server runs PHP ' . PHP_VERSION . '. Choose a newer PHP version in your hosting control panel.');
}

// The "app" folder sits next to this web folder, whatever it is called (public, public_html …).
define('BRIO_PUBLIC', __DIR__);
require dirname(__DIR__) . '/app/brio_logic/bootstrap.php';
require __DIR__ . '/modules/core/helpers.php';
require __DIR__ . '/modules/products/products.php';
require __DIR__ . '/modules/account/account.php';
require __DIR__ . '/modules/site/site.php';

// Let the PHP built-in server serve real static files.
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (PHP_SAPI === 'cli-server' && $uri !== '/' && is_file(__DIR__ . $uri) && !str_ends_with($uri, '.php')) {
    return false;
}

$path = trim(substr($uri, strlen(base_path())), '/');
$path = $path === 'index.php' ? '' : $path;

// /api/* and /admin/* are handled by the backend (app/brio_logic).
if (\Brio\Http\Kernel::handles($path)) {
    \Brio\Http\Kernel::handle($path);
    return;
}

// route => [page file, title]
$routes = [
    ''         => ['home',     'Brio — Fresh, leaf-powered care'],
    'products' => ['products', 'Shop — Brio'],
    'login'    => ['login',    'Sign in — Brio'],
    'register' => ['register', 'Create an account — Brio'],
    'account'  => ['account',  'My account — Brio'],
    'checkout' => ['checkout', 'Checkout — Brio'],
];

$route = $path;
$metaDescription = null;
$cms = null;
$dbReady = site_db_ready();
if (preg_match('#^products/([a-z0-9-]+)$#', $path, $m) && ($product = site_product($m[1])) !== null) {
    // Clean product URL: /products/vitamin-c-e-serum
    $page = 'product';
    $route = 'products';
    $title = $product['name'] . ' — Brio';
    $metaDescription = mb_substr(trim($product['description']), 0, 160);
} elseif ($dbReady) {
    // Every other page, built-in or built by you, is a page in the page builder. A deleted page is a 404.
    if (($cms = site_page($path)) !== null) {
        $page = 'page';
        $title = $cms['systemKey'] === 'home' ? 'Brio — Fresh, leaf-powered care' : $cms['title'] . ' — Brio';
        $metaDescription = $cms['metaDescription'];
    } else {
        http_response_code(404);
        [$page, $title, $route] = ['404', 'Page not found — Brio', '404'];
    }
} elseif (isset($routes[$path])) {
    [$page, $title] = $routes[$path]; // before setup: built-in templates
} else {
    http_response_code(404);
    [$page, $title, $route] = ['404', 'Page not found — Brio', '404'];
}

include __DIR__ . '/components/layout/header.php';
include __DIR__ . '/pages/' . $page . '.php';
include __DIR__ . '/components/layout/footer.php';
