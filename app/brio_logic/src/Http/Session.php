<?php
declare(strict_types=1);

namespace Brio\Http;

/** Secure session start + CSRF token + one-time flash messages. */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        session_name('brio_admin');
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'secure' => $https, 'samesite' => 'Lax']);
        session_start();
    }

    public static function csrfToken(): string
    {
        return $_SESSION['brio_csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function verifyCsrf(?string $token): bool
    {
        return is_string($token) && isset($_SESSION['brio_csrf']) && hash_equals($_SESSION['brio_csrf'], $token);
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) { $_SESSION['brio_flash'][$key] = $value; return null; }
        $v = $_SESSION['brio_flash'][$key] ?? null;
        unset($_SESSION['brio_flash'][$key]);
        return $v;
    }
}
