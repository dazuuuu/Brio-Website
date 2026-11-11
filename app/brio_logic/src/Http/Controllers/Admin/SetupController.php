<?php
declare(strict_types=1);

namespace Brio\Http\Controllers\Admin;

use Brio\Domain\Exceptions\ValidationException;
use Brio\Http\Middleware\Middleware;
use Brio\Http\Request;
use Brio\Http\Response;
use Brio\Http\Session;
use Brio\Infrastructure\Config\Config;
use Brio\Infrastructure\Config\ServerCheck;

/**
 * Two-step installer:
 *   1. database  — MySQL details → create DB, write config, run migrations, seed products
 *   2. admin     — create the first admin account
 * Reached at /setup; Middleware::setupOpen() makes it 404 once an admin exists.
 */
final class SetupController
{
    public function show(Request $r): void
    {
        $step = Config::hasDatabase() && $this->dbReachable() ? 'admin' : 'database';
        Response::view('admin/setup', ['step' => $step, 'errors' => [], 'old' => [], 'notice' => Session::flash('notice'), 'checks' => ServerCheck::run()]);
    }

    public function database(Request $r): void
    {
        try {
            $result = brio('setup')->installDatabase($r->input());
            Config::reload();

            // Reconnecting to a database that already has an admin (e.g. the connection file was lost):
            // nothing to create. Lock setup again and send them to sign in with their existing account.
            if (Middleware::safeHasAdmin()) {
                @file_put_contents(BRIO_SETUP_LOCK, date('c') . "\n", LOCK_EX);
                \Brio\Infrastructure\Logging\Logger::info('Setup: reconnected to existing database');
                Session::flash('notice', 'Reconnected to your existing database. Sign in with your existing admin account.');
                Response::redirect(admin_url('login'));
                return;
            }
            Session::flash('notice', sprintf('Database ready: %d migration(s) ran, %d starter product(s) added.', count($result['migrations']), $result['seeded']));
            Response::redirect(setup_url());
        } catch (ValidationException $e) {
            $old = $r->input();
            unset($old['password']);
            Response::view('admin/setup', ['step' => 'database', 'errors' => $e->errors, 'old' => $old, 'notice' => null, 'message' => $e->getMessage(), 'checks' => ServerCheck::run()], 422);
        }
    }

    public function admin(Request $r): void
    {
        if (!Config::hasDatabase()) { Response::redirect(setup_url()); return; }
        $in = $r->input();
        try {
            brio('auth')->createFirstAdmin((string)($in['name'] ?? ''), (string)($in['email'] ?? ''), (string)($in['password'] ?? ''), (string)($in['confirm'] ?? ''));
            if (file_put_contents(BRIO_SETUP_LOCK, date('c') . "\n", LOCK_EX) === false) {
                \Brio\Infrastructure\Logging\Logger::error('Could not write setup lock file', ['file' => BRIO_SETUP_LOCK]);
            }
            \Brio\Infrastructure\Logging\Logger::info('Setup completed and locked');
            Session::flash('notice', 'Your admin account is ready. Sign in to continue.');
            Response::redirect(admin_url('login'));
        } catch (ValidationException $e) {
            Response::view('admin/setup', ['step' => 'admin', 'errors' => $e->errors, 'old' => ['name' => $in['name'] ?? '', 'email' => $in['email'] ?? ''], 'notice' => null], 422);
        }
    }


    private function dbReachable(): bool
    {
        try { brio('db'); return true; } catch (\Throwable) { return false; }
    }
}
