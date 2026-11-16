<?php
declare(strict_types=1);

namespace Brio\Application\Services;

use Brio\Domain\Customer\Customer;
use Brio\Domain\Customer\CustomerRepository;
use Brio\Domain\Exceptions\AuthException;
use Brio\Domain\Exceptions\RateLimitException;
use Brio\Domain\Exceptions\ValidationException;
use Brio\Infrastructure\Logging\Logger;

/** Shopper accounts: register, sign in/out (PHP session), contact details, password. */
final class CustomerService
{
    private const SESSION_KEY = 'brio_customer_id';
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 300;

    public function __construct(private readonly CustomerRepository $customers) {}

    public function register(array $in): Customer
    {
        $customer = Customer::register($in);
        if ($this->customers->findByEmail($customer->email)) {
            throw new ValidationException(['email' => ['An account with this email already exists. Try signing in.']]);
        }
        $customer = $this->customers->create($customer);
        $this->signIn($customer);
        Logger::info('Customer registered', ['id' => $customer->id]);
        return $customer;
    }

    public function login(string $email, string $password): Customer
    {
        $state = $_SESSION['brio_cust_attempts'] ?? ['n' => 0, 'until' => 0];
        if ($state['until'] > time()) throw new RateLimitException('Too many attempts. Please try again in a few minutes.');

        $customer = $this->customers->findByEmail(trim($email));
        // Unknown emails still cost one password check, so response time doesn't reveal which emails have accounts.
        $ok = $customer ? $customer->verifyPassword($password) : (password_verify($password, password_hash('placeholder', PASSWORD_DEFAULT)) && false);
        if (!$customer || !$ok) {
            $n = $state['n'] + 1;
            $_SESSION['brio_cust_attempts'] = ['n' => $n >= self::MAX_ATTEMPTS ? 0 : $n, 'until' => $n >= self::MAX_ATTEMPTS ? time() + self::LOCK_SECONDS : 0];
            Logger::warning('Failed customer sign-in', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
            throw new AuthException('Incorrect email or password.');
        }

        unset($_SESSION['brio_cust_attempts']);
        $this->signIn($customer);
        $this->customers->touchLogin((int)$customer->id);
        return $customer;
    }

    public function current(): ?Customer
    {
        if (session_status() !== PHP_SESSION_ACTIVE) return null; // visitors without a session are guests
        $id = $_SESSION[self::SESSION_KEY] ?? null;
        return $id ? $this->customers->find((int)$id) : null;
    }

    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) return;
        unset($_SESSION[self::SESSION_KEY]);
        session_regenerate_id(true);
    }

    public function updateDetails(Customer $customer, array $in): Customer
    {
        $customer->updateDetails($in);
        $this->customers->update($customer);
        return $customer;
    }

    public function changePassword(Customer $customer, string $current, string $new, string $confirm): void
    {
        $e = [];
        if (!$customer->verifyPassword($current)) $e['current'][] = 'That is not your current password.';
        if (strlen($new) < 8) $e['new'][] = 'Use at least 8 characters.';
        elseif (strlen($new) > 72) $e['new'][] = 'Use 72 characters or fewer.';
        if ($new !== $confirm) $e['confirm'][] = 'Passwords do not match.';
        if ($e) throw new ValidationException($e);
        $this->customers->updatePassword((int)$customer->id, password_hash($new, PASSWORD_DEFAULT));
        Logger::info('Customer changed password', ['id' => $customer->id]);
    }

    private function signIn(Customer $customer): void
    {
        session_regenerate_id(true); // prevents session fixation
        $_SESSION[self::SESSION_KEY] = $customer->id;
    }
}
