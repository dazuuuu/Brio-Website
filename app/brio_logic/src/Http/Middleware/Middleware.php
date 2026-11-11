<?php
declare(strict_types=1);

namespace Brio\Http\Middleware;

use Brio\Http\Request;
use Brio\Http\Response;
use Brio\Http\Session;
use Brio\Infrastructure\Config\Config;

/** Route guards. Each returns false after sending its own response to stop the request. */
final class Middleware
{
    /** Database configured and an admin exists; otherwise send to the setup wizard. */
    public static function installed(): callable
    {
        return static function (Request $r): bool {
            if (Config::hasDatabase() && self::safeHasAdmin()) return true;
            $r->wantsJson() ? Response::error('Brio is not set up yet.', 503) : Response::redirect(setup_url());
            return false;
        };
    }

    /**
     * Setup is only reachable until the first admin account exists.
     * After that every /setup URL answers 404, as if it never existed.
     */
    public static function setupOpen(): callable
    {
        return static function (Request $r): bool {
            // The lock file keeps setup closed even if the database is down or unreachable.
            if (!is_file(BRIO_SETUP_LOCK)) {
                if (!(Config::hasDatabase() && self::safeHasAdmin())) return true;
                @file_put_contents(BRIO_SETUP_LOCK, date('c') . "\n"); // existing install without a lock: create it
            }
            Response::notFound();
            return false;
        };
    }

    public static function admin(): callable
    {
        return static function (Request $r): bool {
            if (brio('auth')->user()) return true;
            $r->wantsJson() ? Response::error('Please sign in.', 401) : Response::redirect(admin_url('login'));
            return false;
        };
    }

    /** CSRF check for any state-changing request (form field or X-CSRF-Token header). */
    public static function csrf(): callable
    {
        return static function (Request $r): bool {
            if (in_array($r->method, ['GET', 'HEAD'], true)) return true;
            if (Session::verifyCsrf($r->header('X-CSRF-Token') ?? ($_POST['_token'] ?? null))) return true;
            $r->wantsJson() ? Response::error('Your session expired. Refresh the page and try again.', 419)
                            : Response::view('admin/error', ['message' => 'Your session expired. Go back, refresh and try again.'], 419);
            return false;
        };
    }

    /** Public form posts must come from our own pages (blocks cross-site form abuse). */
    public static function sameOrigin(): callable
    {
        return static function (Request $r): bool {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if ($origin === '') return true; // same-origin form posts from some browsers omit it
            $port = parse_url($origin, PHP_URL_PORT);
            $host = (string)parse_url($origin, PHP_URL_HOST) . ($port ? ":$port" : '');
            if (strcasecmp($host, (string)($_SERVER['HTTP_HOST'] ?? '')) === 0) return true;
            Response::error('Request blocked.', 403);
            return false;
        };
    }

    public static function safeHasAdmin(): bool
    {
        try { return brio('auth')->hasAdmin(); } catch (\Throwable) { return false; }
    }
}
