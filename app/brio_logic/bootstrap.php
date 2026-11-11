<?php
/**
 * Brio backend bootstrap.
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

/** Admin line icons (24px grid, stroke = currentColor). */
function adm_icon(string $name, int $size = 18): string
{
    static $p = [
        'bag'      => '<path d="M6 7h12l1 13H5L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/>',
        'box'      => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>',
        'tag'      => '<path d="M3 12V4h8l10 10-8 8L3 12Z"/><circle cx="7.5" cy="8.5" r="1.5"/>',
        'star'     => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9Z"/>',
        'store'    => '<path d="M4 10v10h16V10"/><path d="M3 10 5 4h14l2 6c0 1.7-1.3 3-3 3s-3-1.3-3-3c0 1.7-1.3 3-3 3s-3-1.3-3-3c0 1.7-1.3 3-3 3s-3-1.3-3-3Z"/><path d="M10 20v-5h4v5"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'logout'   => '<path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'export'   => '<path d="M12 15V3M8 7l4-4 4 4"/><path d="M4 14v6h16v-6"/>',
        'sliders'  => '<path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>',
        'filter'   => '<path d="M4 5h16l-6 8v6l-4-2v-4L4 5Z"/>',
        'sort'     => '<path d="M7 4v16M3 8l4-4 4 4M17 20V4M13 16l4 4 4-4"/>',
        'more'     => '<circle cx="5" cy="12" r="1.3"/><circle cx="12" cy="12" r="1.3"/><circle cx="19" cy="12" r="1.3"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'table'    => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M9 10v10"/>',
        'chev-l'   => '<path d="m15 18-6-6 6-6"/>',
        'chev-r'   => '<path d="m9 18 6-6-6-6"/>',
        'chev-d'   => '<path d="m6 9 6 6 6-6"/>',
        'coins'    => '<ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v6c0 1.7 3.1 3 7 3s7-1.3 7-3V6M5 12v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/>',
        'chart'    => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'cart'     => '<path d="M3 4h2.5l2.2 11h9.8l2-8H6.4"/><circle cx="9.5" cy="19.5" r="1.4"/><circle cx="17" cy="19.5" r="1.4"/>',
        'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'image'    => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-5-5-8 8"/>',
        'file'     => '<path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5M10 13h6M10 17h6"/>',
        'list'     => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'cog'      => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
        'trash'    => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'up'       => '<path d="m6 15 6-6 6 6"/>',
        'down'     => '<path d="m6 9 6 6 6-6"/>',
        'edit'     => '<path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/>',
        'eye'      => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'chat'     => '<path d="M4 5h16v11H9l-5 4V5Z"/>',
    ];
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? '') . '</svg>';
}
