<?php
// Storefront access to the CMS side of the backend: menus, hero slides, pages, settings.
// Every helper degrades gracefully (empty/default) if the database isn't ready, so the store never fatals.

use Brio\Infrastructure\Config\Config;
use Brio\Infrastructure\Logging\Logger;

/** Run a backend call; on any failure log it and return $fallback. */
function site_try(callable $fn, mixed $fallback = null): mixed
{
    if (!Config::hasDatabase()) return $fallback;
    try {
        return $fn();
    } catch (Throwable $e) {
        Logger::error('Storefront data error', ['error' => $e->getMessage(), 'at' => basename($e->getFile()) . ':' . $e->getLine()]);
        return $fallback;
    }
}

/** True when the backend database is configured and reachable (otherwise the store falls back to built-in templates). */
function site_db_ready(): bool
{
    static $ready = null;
    return $ready ??= (bool)site_try(static fn() => brio('db') instanceof PDO, false);
}

/** Turn a resolved link ("products/serum", "", "https://…") into a URL for an href. */
function site_href(?string $path): string
{
    if ($path === null) return '#';
    if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $path)) return $path; // https:, mailto:, tel:
    return url($path);
}

/** @return array<int,array{label:string,href:string,type:string,target:?string,newTab:bool}> */
function site_nav(string $location): array
{
    static $cache = [];
    if (!isset($cache[$location])) {
        $items = site_try(fn() => brio('navigation')->resolved($location));
        $cache[$location] = $items ?? ($location === 'header' ? [
            ['label' => 'All Products', 'href' => 'products', 'type' => 'shop', 'target' => null, 'newTab' => false],
            ['label' => 'Skin Care', 'href' => 'products?category=skin-care', 'type' => 'category', 'target' => 'skin-care', 'newTab' => false],
            ['label' => 'Bath & Body', 'href' => 'products?category=bath-body', 'type' => 'category', 'target' => 'bath-body', 'newTab' => false],
        ] : [
            ['label' => 'My account', 'href' => 'account', 'type' => 'url', 'target' => '/account', 'newTab' => false],
        ]);
    }
    return $cache[$location];
}

function nav_is_active(array $item, string $route, string $path): bool
{
    switch ($item['type']) {
        case 'category': return $route === 'products' && ($_GET['category'] ?? null) === $item['target'];
        case 'shop': return $route === 'products' && empty($_GET['category']);
        case 'home': return $path === '';
        default:
            if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $item['href'])) return false;
            return trim((string)parse_url($item['href'], PHP_URL_PATH), '/') === $path;
    }
}

/** Active hero slides ready to render; [] means "show the built-in hero". */
function site_hero(): array
{
    return site_try(fn() => brio('hero')->forStorefront(), []);
}

/** Header options from Settings (search box, account icon). Defaults to showing everything. */
function site_header_flags(): array
{
    static $flags = null;
    return $flags ??= site_try(static fn() => brio('settings')->flags(), ['showSearch' => true, 'showAccountIcon' => true]);
}

function site_whatsapp_enabled(): bool
{
    return (bool)site_try(fn() => brio('orders')->whatsappEnabled(), false);
}

/** A CMS page by clean URL. Drafts are visible only to a signed-in admin using ?preview=1. */
function site_page(string $path): ?array
{
    $drafts = false;
    if (isset($_GET['preview'], $_COOKIE['brio_admin'])) {
        $drafts = (bool)site_try(function () {
            \Brio\Http\Session::start();
            $isAdmin = brio('auth')->user() !== null;
            session_write_close();
            return $isAdmin;
        }, false);
    }
    return site_try(fn() => brio('pages')->render($path, $drafts));
}

function site_product(string $slug): ?array
{
    if (Config::hasDatabase()) {
        $found = site_try(fn() => brio('products')->findBySlug($slug));
        if ($found !== null) return $found;
    }
    foreach (fallback_products('all', '', null, null) as $p) if ($p['slug'] === $slug) return $p;
    return null;
}

function site_related(array $product): array
{
    return site_try(fn() => brio('products')->related($product['category'], (int)$product['id']), []);
}
