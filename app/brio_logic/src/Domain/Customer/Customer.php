<?php
declare(strict_types=1);

namespace Brio\Domain\Customer;

use Brio\Domain\Exceptions\ValidationException;
use Brio\Domain\Order\Order;

/** A shopper's account: login details plus the contact details used to order quickly. */
final class Customer
{
    public function __construct(
        public ?int $id,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $passwordHash,
        public string $phone,
        public string $whatsapp,
        public ?string $address,
        public ?string $createdAt = null,
        public ?string $googleSub = null,
        public bool $hasPassword = true,
        public ?string $emailVerifiedAt = null,
        public ?string $verifySentAt = null,
    ) {}

    /** Sign-up needs only an email and a password; name and contact details are added later (profile or checkout). @throws ValidationException */
    public static function register(array $in): self
    {
        $e = [];
        $email = strtolower(trim((string)($in['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) $e['email'][] = 'Enter a valid email address.';

        $password = (string)($in['password'] ?? '');
        if (strlen($password) < 8) $e['password'][] = 'Use at least 8 characters.';
        elseif (strlen($password) > 72) $e['password'][] = 'Use 72 characters or fewer.';
        if ($password !== (string)($in['confirm'] ?? '')) $e['confirm'][] = 'Passwords do not match.';
        if ($e) throw new ValidationException($e);

        return new self(null, '', '', $email, password_hash($password, PASSWORD_DEFAULT), '', '', null);
    }

    public function isVerified(): bool { return $this->emailVerifiedAt !== null; }

    /** What to call them: first name, or the part of the email before the @. */
    public function displayName(): string
    {
        return $this->firstName !== '' ? $this->firstName : ucfirst((string)strstr($this->email, '@', true));
    }

    /** A customer created by "Continue with Google": no password to remember, contact details still to fill in. */
    public static function fromGoogle(string $email, string $first, string $last, string $sub): self
    {
        $unguessable = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT); // nobody knows it; they sign in with Google
        return new self(null, mb_substr($first, 0, 60), mb_substr($last, 0, 60), $email, $unguessable, '', '', null, null, $sub, false, date('Y-m-d H:i:s')); // Google has already verified the email
    }

    /** Update name and contact details (not the email or password). @throws ValidationException */
    public function updateDetails(array $in): void
    {
        $d = self::details($in + ['firstName' => $this->firstName, 'lastName' => $this->lastName]);
        [$this->firstName, $this->lastName, $this->phone, $this->whatsapp, $this->address] = [$d['firstName'], $d['lastName'], $d['phone'], $d['whatsapp'], $d['address']];
    }

    /** Shared validation for the name + contact fields. @return array{firstName:string,lastName:string,phone:string,whatsapp:string,address:?string} */
    private static function details(array $in): array
    {
        $e = [];
        $first = mb_substr(trim((string)($in['firstName'] ?? '')), 0, 60);
        $last = mb_substr(trim((string)($in['lastName'] ?? '')), 0, 60);
        if ($first === '') $e['firstName'][] = 'Enter your first name.';

        $phone = trim((string)($in['phone'] ?? ''));
        if (!Order::phoneOk($phone)) $e['phone'][] = 'Enter a valid phone number.';
        $whatsapp = trim((string)($in['whatsapp'] ?? ''));
        if ($whatsapp === '') $whatsapp = $phone; // empty means "same as my phone"
        elseif (!Order::phoneOk($whatsapp)) $e['whatsapp'][] = 'Enter a valid WhatsApp number.';

        $address = mb_substr(trim((string)($in['address'] ?? '')), 0, 300);
        if ($e) throw new ValidationException($e);
        return ['firstName' => $first, 'lastName' => $last, 'phone' => $phone, 'whatsapp' => $whatsapp, 'address' => $address !== '' ? $address : null];
    }

    public function verifyPassword(string $plain): bool { return password_verify($plain, $this->passwordHash); }

    public function fullName(): string { return trim($this->firstName . ' ' . $this->lastName); }

    /** True when there's enough to place an order without asking again. */
    public function hasContactDetails(): bool { return Order::phoneOk($this->phone) && Order::phoneOk($this->whatsapp); }

    /** Safe to send to the browser (never includes the password hash). */
    public function toArray(): array
    {
        return ['id' => $this->id, 'firstName' => $this->firstName, 'lastName' => $this->lastName, 'fullName' => $this->fullName(), 'displayName' => $this->displayName(), 'emailVerified' => $this->isVerified(), 'email' => $this->email,
                'phone' => $this->phone, 'whatsapp' => $this->whatsapp, 'address' => $this->address, 'hasContactDetails' => $this->hasContactDetails(),
                'createdAt' => $this->createdAt, 'hasPassword' => $this->hasPassword, 'viaGoogle' => $this->googleSub !== null];
    }
}
