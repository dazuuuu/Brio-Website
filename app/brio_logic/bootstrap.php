<?php
/**
 * Store backend bootstrap.
 *
 * Layers (dependencies point inwards only):
 *   Domain          entities, rules, repository contracts   (no framework, no SQL)
 *   Application     use-case services that orchestrate the domain
 *   Infrastructure  PDO repositories, migrations, files, logging, config
 *   Http            router, request/response, controllers, middleware
 */
declare(strict_types=1);

define('BRIO_ROOT', __DIR__);
// The web folder (public, public_html, htdocs …). index.php sets it; this default covers command-line use.
if (!defined('BRIO_PUBLIC')) define('BRIO_PUBLIC', dirname(__DIR__, 2) . '/public');

// PSR-4 style autoloader: Brio\Foo\Bar -> src/Foo/Bar.php
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'Brio\\')) return;
    $file = BRIO_ROOT . '/src/' . str_replace('\\', '/', substr($class, 5)) . '.php';
    if (is_file($file)) require $file;
});

use Brio\Infrastructure\Config\Config;
use Brio\Infrastructure\Logging\Logger;

Logger::init(BRIO_ROOT . '/storage/logs');
Config::load(BRIO_ROOT . '/config');
date_default_timezone_set((string)Config::get('app.timezone', 'UTC'));

/** Base URL path the site is served from ("" at the domain root). */
function brio_base(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    return rtrim($dir === '/' || $dir === '.' ? '' : $dir, '/');
}

function admin_url(string $path = ''): string
{
    return brio_base() . '/admin' . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

/** Written when the first admin is created; while it exists /setup is gone for good. */
define('BRIO_SETUP_LOCK', BRIO_ROOT . '/storage/setup.lock');

function setup_url(string $path = ''): string
{
    return brio_base() . '/setup' . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

/** Full address of the site, e.g. https://example.com (used for links that leave the site, like WhatsApp messages). */
function brio_public_url(): string
{
    $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $host = preg_replace('/[^A-Za-z0-9.:\-]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return ($https ? 'https' : 'http') . '://' . $host . brio_base();
}

function brio_asset(string $path): string
{
    return brio_base() . '/' . ltrim($path, '/');
}

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/** Small service container: builds each service once, on first use. */
function brio(string $id): object
{
    static $services = [];
    return $services[$id] ??= (require BRIO_ROOT . '/config/services.php')[$id]();
}

/** Admin icons: Bootstrap Icons (icon font). The names below are the ones the admin views use. */
function adm_icon(string $name, int $size = 18): string
{
    static $map = [
        'bag' => 'handbag-fill', 'box' => 'box-seam', 'tag' => 'tags', 'star' => 'star', 'store' => 'shop', 'search' => 'search',
        'logout' => 'box-arrow-right', 'plus' => 'plus-lg', 'export' => 'download', 'upload' => 'cloud-arrow-up', 'sliders' => 'sliders',
        'filter' => 'funnel', 'sort' => 'arrow-down-up', 'more' => 'three-dots', 'calendar' => 'calendar3', 'table' => 'table',
        'chev-l' => 'chevron-left', 'chev-r' => 'chevron-right', 'chev-d' => 'chevron-down', 'coins' => 'coin', 'chart' => 'bar-chart-line',
        'cart' => 'cart3', 'menu' => 'list', 'image' => 'image', 'file' => 'file-earmark-text', 'list' => 'list-ul', 'cog' => 'gear',
        'trash' => 'trash3', 'up' => 'chevron-up', 'down' => 'chevron-down', 'edit' => 'pencil-square', 'eye' => 'eye', 'chat' => 'chat-dots',
        'check' => 'check-lg', 'x' => 'x-lg', 'megaphone' => 'megaphone', 'video' => 'camera-video',
    ];
    return '<i class="bi bi-' . ($map[$name] ?? 'circle') . '" style="font-size:' . $size . 'px" aria-hidden="true"></i>';
}

/**
 * The store's identity: name, tagline, logos, icon location, share-image mode, Google client id.
 * Everything has a default, so pages render even before the database exists. Paths are relative to the web folder.
 */
function brand(): array
{
    static $b = null;
    if ($b !== null) return $b;
    $b = ['name' => 'Asiva Organics', 'tagline' => 'Natural skin & body care', 'logo' => 'assets/img/logo.png', 'logoDark' => 'assets/img/logo-dark.png',
          'hasDarkLogo' => true, 'customLogo' => false, 'iconBase' => '', 'iconVersion' => '', 'ogLogo' => 'assets/img/og-default.png',
          'shareMode' => 'popular', 'shareImagePath' => '', 'googleClientId' => ''];
    try {
        if (\Brio\Infrastructure\Config\Config::hasDatabase()) {
            $s = brio('settings')->get();
            $b['name'] = $s['store_name'] !== '' ? $s['store_name'] : $b['name'];
            $b['tagline'] = $s['store_tagline'];
            if ($s['logo'] !== '') {
                $b['customLogo'] = true;
                $b['logo'] = $s['logo'];
                $b['logoDark'] = $s['logo_dark'];
                $b['hasDarkLogo'] = $s['logo_dark'] !== '';
                $b['ogLogo'] = is_file(BRIO_PUBLIC . '/uploads/brand/og-logo.png') ? 'uploads/brand/og-logo.png' : $s['logo'];
            } elseif ($s['logo_dark'] !== '') {
                $b['logoDark'] = $s['logo_dark'];
            }
            if ($s['site_icon'] !== '') { $b['iconBase'] = 'uploads/brand/icons/'; $b['iconVersion'] = $s['site_icon']; }
            $b['shareMode'] = $s['share_image'];
            $b['shareImagePath'] = $s['share_image_path'];
            $b['googleClientId'] = $s['google_client_id'];
        }
    } catch (\Throwable) {
        // keep the defaults
    }
    return $b;
}

/** Admin accent colour -> the CSS variables the admin stylesheet uses (tints, shades, readable text colour). */
function adm_theme_vars(string $hex): array
{
    if (!preg_match('/^#[0-9a-f]{6}$/i', $hex)) $hex = '#f25c1f';
    [$r, $g, $b] = array_map('hexdec', str_split(ltrim($hex, '#'), 2));
    $mix = static fn(int $to, float $t): string => sprintf('#%02x%02x%02x', ...array_map(static fn($c) => (int)round($c + ($to - $c) * $t), [$r, $g, $b]));
    return [
        '--adm-brand' => strtolower($hex),
        '--adm-brand-dark' => $mix(0, 0.14),
        '--adm-brand-soft' => $mix(255, 0.9),
        '--adm-brand-row' => $mix(255, 0.86),
        '--adm-brand-rgb' => "$r, $g, $b",
        '--adm-on-brand' => (0.299 * $r + 0.587 * $g + 0.114 * $b) > 150 ? '#1f1f1f' : '#ffffff',
    ];
}

/** <style> block with the saved accent colour (empty until the database is ready). */
function adm_theme_style(): string
{
    try {
        $hex = \Brio\Infrastructure\Config\Config::hasDatabase() ? (string)brio('settings')->get()['admin_accent'] : '#f25c1f';
    } catch (\Throwable) {
        $hex = '#f25c1f';
    }
    $css = '';
    foreach (adm_theme_vars($hex) as $k => $v) $css .= "$k:$v;";
    return '<style>:root{' . $css . '}</style>';
}
