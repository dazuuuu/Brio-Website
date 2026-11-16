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
    ) {}

    /** @throws ValidationException */
    public static function register(array $in): self
    {
        $e = [];
        $email = strtolower(trim((string)($in['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) $e['email'][] = 'Enter a valid email address.';

        $password = (string)($in['password'] ?? '');
        if (strlen($password) < 8) $e['password'][] = 'Use at least 8 characters.';
        elseif (strlen($password) > 72) $e['password'][] = 'Use 72 characters or fewer.';
        if ($password !== (string)($in['confirm'] ?? '')) $e['confirm'][] = 'Passwords do not match.';
        if (!filter_var($in['terms'] ?? false, FILTER_VALIDATE_BOOLEAN)) $e['terms'][] = 'Please accept the terms to create your account.';

        try {
            $d = self::details($in);
        } catch (ValidationException $x) {
            $e += $x->errors;
            $d = null;
        }
        if ($e) throw new ValidationException($e);

        return new self(null, $d['firstName'], $d['lastName'], $email, password_hash($password, PASSWORD_DEFAULT), $d['phone'], $d['whatsapp'], $d['address']);
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
        return ['id' => $this->id, 'firstName' => $this->firstName, 'lastName' => $this->lastName, 'fullName' => $this->fullName(), 'email' => $this->email,
                'phone' => $this->phone, 'whatsapp' => $this->whatsapp, 'address' => $this->address, 'hasContactDetails' => $this->hasContactDetails(),
                'createdAt' => $this->createdAt];
    }
}
