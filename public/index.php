<?php
// Front controller: routes clean URLs to pages in /pages.
declare(strict_types=1);

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('This site needs PHP 8.1 or newer. This server runs PHP ' . PHP_VERSION . '. Choose a newer PHP version in your hosting control panel.');
}

// The "app" folder sits next to this web folder, whatever it is called (public, public_html …).
define('BRIO_PUBLIC', __DIR__);
require dirname(__DIR__) . '/app/brio_logic/bootstrap.php';
require __DIR__ . '/modules/core/helpers.php';
require __DIR__ . '/modules/products/products.php';
require __DIR__ . '/modules/site/site.php';
require __DIR__ . '/modules/site/seo.php';

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

// robots.txt, sitemap.xml and the web manifest are generated from the store's own data.
if (in_array($path, ['robots.txt', 'sitemap.xml', 'site.webmanifest'], true)) {
    seo_serve($path);
    return;
}

// Built-in templates, used only before the database is set up.
$routes = [
    ''         => ['home', 'Home'],
    'products' => ['products', 'Shop'],
    'login'    => ['login', 'Sign in'],
    'register' => ['register', 'Create an account'],
    'account'  => ['account', 'My account'],
    'checkout' => ['checkout', 'Checkout'],
];

$brandInfo = brand();
$siteName = $brandInfo['name'];
$homeTitle = $siteName . ($brandInfo['tagline'] !== '' ? ' — ' . $brandInfo['tagline'] : '');
$route = $path;
$cms = null;
$product = null;
$noindexRoutes = ['checkout', 'account', 'login', 'register'];
$dbReady = site_db_ready();
if ($path === 'verify-email' && $dbReady) {
    // The link in the confirmation email. Never cached or indexed; the token is single-use.
    header('Cache-Control: no-store');
    header('Referrer-Policy: no-referrer');
    $page = 'verify-email';
    $route = 'verify-email';
    $seo = seo_context(['title' => 'Confirm your email | ' . $siteName, 'path' => $path, 'noindex' => true]);
} elseif (preg_match('#^products/([a-z0-9-]+)$#', $path, $m) && ($product = site_product($m[1])) !== null) {
    // Clean product URL: /products/vitamin-c-e-serum
    $page = 'product';
    $route = 'products';
    $seo = seo_context(['title' => $product['name'] . ' | ' . $siteName, 'description' => short_description($product['description'] ?? '', 160), 'path' => $path, 'product' => $product]);
} elseif ($dbReady) {
    // Every other page, built-in or built by you, is a page in the page builder. A deleted page is a 404.
    if (($cms = site_page($path)) !== null) {
        $page = 'page';
        $title = $cms['systemKey'] === 'home' ? $homeTitle : $cms['title'] . ' | ' . $siteName;
        $seo = seo_context(['title' => $title, 'description' => $cms['metaDescription'], 'path' => $path, 'noindex' => in_array($path, $noindexRoutes, true) || !$cms['isPublished']]);
    } else {
        http_response_code(404);
        [$page, $route] = ['404', '404'];
        $seo = seo_context(['title' => 'Page not found | ' . $siteName, 'path' => $path, 'noindex' => true]);
    }
} elseif (isset($routes[$path])) {
    [$page, $label] = $routes[$path]; // before setup: built-in templates
    $seo = seo_context(['title' => $path === '' ? $homeTitle : $label . ' | ' . $siteName, 'path' => $path, 'noindex' => in_array($path, $noindexRoutes, true)]);
} else {
    http_response_code(404);
    [$page, $route] = ['404', '404'];
    $seo = seo_context(['title' => 'Page not found | ' . $siteName, 'path' => $path, 'noindex' => true]);
}
$title = $seo['title'];

include __DIR__ . '/components/layout/header.php';
include __DIR__ . '/pages/' . $page . '.php';
include __DIR__ . '/components/layout/footer.php';
