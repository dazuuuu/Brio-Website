<?php
declare(strict_types=1);

namespace Brio\Application\Services;

use Brio\Domain\Admin\Admin;
use Brio\Domain\Admin\AdminRepository;
use Brio\Domain\Exceptions\AuthException;
use Brio\Domain\Exceptions\RateLimitException;
use Brio\Domain\Exceptions\ValidationException;
use Brio\Infrastructure\Logging\Logger;

/** Admin authentication backed by the PHP session. */
final class AuthService
{
    private const SESSION_KEY = 'brio_admin_id';
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 300;

    public function __construct(private readonly AdminRepository $admins) {}

    public function hasAdmin(): bool
    {
        return $this->admins->exists();
    }

    public function createFirstAdmin(string $name, string $email, string $password, string $confirm): Admin
    {
        if ($this->admins->exists()) throw new AuthException('An admin account already exists.');
        $e = [];
        $email = strtolower(trim($email));
        if (trim($name) === '') $e['name'][] = 'Enter your name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $e['email'][] = 'Enter a valid email address.';
        if (strlen($password) < 8) $e['password'][] = 'Use at least 8 characters.';
        if ($password !== $confirm) $e['confirm'][] = 'Passwords do not match.';
        if ($e) throw new ValidationException($e);

        $admin = $this->admins->create(new Admin(null, trim($name), $email, password_hash($password, PASSWORD_DEFAULT)));
        Logger::info('Admin account created', ['email' => $email]);
        return $admin;
    }

    public function attempt(string $email, string $password): Admin
    {
        $attempts = $_SESSION['brio_login_attempts'] ?? ['n' => 0, 'until' => 0];
        if ($attempts['until'] > time()) throw new AuthException('Too many attempts. Try again in a few minutes.');

        $admin = $this->admins->findByEmail(trim($email));
        if (!$admin || !$admin->verifyPassword($password)) {
            $n = $attempts['n'] + 1;
            $_SESSION['brio_login_attempts'] = ['n' => $n >= self::MAX_ATTEMPTS ? 0 : $n, 'until' => $n >= self::MAX_ATTEMPTS ? time() + self::LOCK_SECONDS : 0];
            Logger::warning('Failed admin login', ['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
            throw new AuthException('Incorrect email or password.');
        }

        unset($_SESSION['brio_login_attempts']);
        session_regenerate_id(true); // prevent session fixation
        $_SESSION[self::SESSION_KEY] = $admin->id;
        $this->admins->touchLogin((int)$admin->id);
        Logger::info('Admin signed in', ['id' => $admin->id]);
        return $admin;
    }

    /** Re-asks for the signed-in admin's password before a dangerous action. Locks for 5 minutes after 5 wrong tries. */
    public function confirmPassword(string $plain): void
    {
        $admin = $this->user() ?? throw new AuthException('Please sign in.');
        $state = $_SESSION['brio_confirm_attempts'] ?? ['n' => 0, 'until' => 0];
        if ($state['until'] > time()) throw new RateLimitException('Too many wrong passwords. Try again in a few minutes.');

        if (!$admin->verifyPassword($plain)) {
            $n = $state['n'] + 1;
            $_SESSION['brio_confirm_attempts'] = ['n' => $n >= self::MAX_ATTEMPTS ? 0 : $n, 'until' => $n >= self::MAX_ATTEMPTS ? time() + self::LOCK_SECONDS : 0];
            Logger::warning('Wrong password on a confirmation', ['admin' => $admin->id]);
            throw new ValidationException(['password' => ['That password is not correct.']], 'That password is not correct.');
        }
        unset($_SESSION['brio_confirm_attempts']);
    }

    public function user(): ?Admin
    {
        $id = $_SESSION[self::SESSION_KEY] ?? null;
        return $id ? $this->admins->find((int)$id) : null;
    }

    public function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        session_regenerate_id(true);
    }
}
