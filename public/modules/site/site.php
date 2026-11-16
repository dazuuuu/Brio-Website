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

/** The signed-in shopper (array) or null. Only touches the session when the visitor already has the shopper cookie. */
function site_customer(): ?array
{
    static $cached = false;
    if ($cached !== false) return $cached;
    $cached = null;
    if (isset($_COOKIE['brio_customer']) && site_db_ready()) {
        $cached = site_try(static function () {
            \Brio\Http\Session::start('brio_customer');
            $customer = brio('customers')->current();
            session_write_close(); // release the session lock straight away
            return $customer?->toArray();
        });
    }
    return $cached;
}

/** A safe same-site path from ?redirect= (never an outside address). */
function site_redirect_param(): string
{
    $r = (string)($_GET['redirect'] ?? '');
    return preg_match('#^/(?!/)[^\\\\\s]*$#', $r) ? $r : '';
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

/** True when the visitor is signed in to the admin (used for draft-page and ad previews). Only checks if they hold the admin cookie. */
function site_is_admin(): bool
{
    static $is = null;
    if ($is !== null) return $is;
    $is = false;
    if (isset($_COOKIE['brio_admin']) && site_db_ready()) {
        $is = (bool)site_try(static function () {
            \Brio\Http\Session::start('brio_admin');
            $ok = brio('auth')->user() !== null;
            session_write_close();
            return $ok;
        }, false);
    }
    return $is;
}

/** A CMS page by clean URL. Drafts are visible only to a signed-in admin using ?preview=1. */
function site_page(string $path): ?array
{
    $drafts = isset($_GET['preview']) && site_is_admin();
    return site_try(fn() => brio('pages')->render($path, $drafts));
}

/** Pop-up ads for this page, with every path turned into a real URL for the browser. */
function site_ads(string $route): array
{
    $ads = [];
    if (isset($_GET['ad_preview']) && site_is_admin()) {
        $preview = site_try(static fn() => brio('ads')->preview((int)$_GET['ad_preview']));
        if ($preview) $ads = [$preview];
    } else {
        $ads = site_try(static fn() => brio('ads')->forStorefront($route), []);
    }
    return array_map(static function (array $ad): array {
        if (!empty($ad['product'])) $ad['product']['image'] = $ad['product']['image'] !== '' ? url($ad['product']['image']) : '';
        if (!empty($ad['media'])) $ad['media']['src'] = url($ad['media']['src']);
        if (!empty($ad['cta'])) $ad['cta']['href'] = site_href($ad['cta']['href']);
        return $ad;
    }, $ads);
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
