<?php
declare(strict_types=1);

namespace Brio\Application\Services;

use Brio\Domain\Customer\Customer;
use Brio\Domain\Customer\CustomerRepository;
use Brio\Domain\Exceptions\AuthException;
use Brio\Domain\Exceptions\RateLimitException;
use Brio\Domain\Exceptions\ValidationException;
use Brio\Infrastructure\Logging\Logger;
use Brio\Infrastructure\Mail\Mailer;

/** Shopper accounts: register, sign in/out (PHP session), contact details, password. */
final class CustomerService
{
    private const SESSION_KEY = 'brio_customer_id';
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 300;

    private const VERIFY_HOURS = 48;
    private const RESEND_SECONDS = 60;

    /** True when the confirmation email left our server on the last register() call. */
    public bool $mailSent = false;

    /**
     * @param \Closure():string $googleClientId reads the Client ID saved in Settings
     * @param \Closure():string $storeName
     */
    public function __construct(
        private readonly CustomerRepository $customers,
        private readonly GoogleVerifier $google,
        private readonly \Closure $googleClientId,
        private readonly Mailer $mailer,
        private readonly string $publicUrl,
        private readonly \Closure $storeName,
    ) {}

    /** Creates the account (email + password only), signs them in and emails a confirmation link. */
    public function register(array $in): Customer
    {
        $customer = Customer::register($in);
        if ($this->customers->findByEmail($customer->email)) {
            throw new ValidationException(['email' => ['An account with this email already exists. Try signing in.']]);
        }
        $customer = $this->customers->create($customer);
        $this->signIn($customer);
        Logger::info('Customer registered', ['id' => $customer->id]);
        $this->mailSent = $this->sendVerification($customer, true);
        return $customer;
    }

    /** Emails a fresh confirmation link. Throws if asked again within a minute (unless $force). */
    public function sendVerification(Customer $customer, bool $force = false): bool
    {
        if ($customer->isVerified()) return true;
        if (!$force && $customer->verifySentAt !== null && time() - strtotime($customer->verifySentAt) < self::RESEND_SECONDS) {
            throw new RateLimitException('We just sent you an email. Please wait a minute before asking for another.');
        }
        $token = bin2hex(random_bytes(32));
        $now = date('Y-m-d H:i:s');
        $this->customers->setVerification((int)$customer->id, hash('sha256', $token), date('Y-m-d H:i:s', time() + self::VERIFY_HOURS * 3600), $now);
        $customer->verifySentAt = $now;

        $store = (string)($this->storeName)();
        $link = rtrim($this->publicUrl, '/') . '/verify-email?token=' . $token;
        $hi = htmlspecialchars($customer->displayName(), ENT_QUOTES);
        $storeH = htmlspecialchars($store, ENT_QUOTES);
        $html = '<div style="font-family:Arial,sans-serif;max-width:480px;margin:auto;color:#1a1a1a">'
            . "<h2 style=\"color:#2e9e4f\">Confirm your email</h2><p>Hi $hi,</p>"
            . "<p>Thanks for creating an account at <b>$storeH</b>. Please confirm your email address so you can track your orders.</p>"
            . '<p style="margin:28px 0"><a href="' . htmlspecialchars($link, ENT_QUOTES) . '" style="background:#2e9e4f;color:#fff;padding:12px 24px;border-radius:999px;text-decoration:none;font-weight:bold">Confirm my email</a></p>'
            . '<p style="font-size:13px;color:#555">Or paste this link into your browser:<br>' . htmlspecialchars($link, ENT_QUOTES) . '</p>'
            . '<p style="font-size:13px;color:#555">The link works for ' . self::VERIFY_HOURS . ' hours. If you did not create this account, you can ignore this email.</p></div>';
        $text = "Hi {$customer->displayName()},\n\nConfirm your email for $store by opening this link (valid " . self::VERIFY_HOURS . " hours):\n$link\n\nIf you did not create this account, ignore this email.";
        return $this->mailer->send($customer->email, "Confirm your email for $store", $html, $text);
    }

    /** Called when someone opens the link from their email. */
    public function verifyEmail(string $token): Customer
    {
        $customer = preg_match('/^[a-f0-9]{64}$/', $token) ? $this->customers->findByVerifyToken(hash('sha256', $token)) : null;
        if ($customer === null) throw new ValidationException(['token' => ['This link has expired or was already used.']]);
        $this->customers->markVerified((int)$customer->id);
        $customer->emailVerifiedAt = date('Y-m-d H:i:s');
        Logger::info('Customer verified email', ['id' => $customer->id]);
        return $customer;
    }

    /** After an order: keep what they typed so they are never asked again. Only fills blanks; never overwrites saved details. */
    public function rememberFromOrder(Customer $customer, array $in): void
    {
        $name = trim((string)($in['name'] ?? ''));
        [$first, $last] = array_pad(explode(' ', $name, 2), 2, '');
        $phone = $customer->phone !== '' ? $customer->phone : trim((string)($in['phone'] ?? ''));
        try {
            $customer->updateDetails([
                'firstName' => $customer->firstName !== '' ? $customer->firstName : $first,
                'lastName' => $customer->lastName !== '' ? $customer->lastName : trim($last),
                'phone' => $phone,
                'whatsapp' => $customer->whatsapp !== '' ? $customer->whatsapp : trim((string)($in['whatsapp'] ?? '')),
                'address' => $customer->address !== null && $customer->address !== '' ? $customer->address : (string)($in['address'] ?? ''),
            ]);
            $this->customers->update($customer);
        } catch (ValidationException) {
            // incomplete or invalid details: skip silently, the order itself already went through
        }
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

    /** Sign in or sign up with a Google ID token. A new Google user still has to add a phone number before ordering. */
    public function loginWithGoogle(string $credential): Customer
    {
        $state = $_SESSION['brio_cust_attempts'] ?? ['n' => 0, 'until' => 0];
        if ($state['until'] > time()) throw new RateLimitException('Too many attempts. Please try again in a few minutes.');

        try {
            $g = $this->google->verify($credential, ($this->googleClientId)());
        } catch (AuthException $e) {
            $n = $state['n'] + 1;
            $_SESSION['brio_cust_attempts'] = ['n' => $n >= self::MAX_ATTEMPTS ? 0 : $n, 'until' => $n >= self::MAX_ATTEMPTS ? time() + self::LOCK_SECONDS : 0];
            throw $e;
        }

        $customer = $this->customers->findByGoogleSub($g['sub']);
        if ($customer === null && ($byEmail = $this->customers->findByEmail($g['email'])) !== null) {
            // Same email as an existing account: Google has verified they own it, so connect the two.
            $this->customers->linkGoogle((int)$byEmail->id, $g['sub']);
            $byEmail->googleSub = $g['sub'];
            $customer = $byEmail;
        }
        if ($customer === null) {
            $customer = $this->customers->create(Customer::fromGoogle($g['email'], $g['first'], $g['last'], $g['sub']));
            Logger::info('Customer registered with Google', ['id' => $customer->id]);
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
        if (!$customer->hasPassword) throw new ValidationException(['current' => ['You sign in with Google, so there is no password to change.']]);
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
