<?php
declare(strict_types=1);

namespace Brio\Domain\Customer;

interface CustomerRepository
{
    public function find(int $id): ?Customer;

    public function findByEmail(string $email): ?Customer;

    public function findByGoogleSub(string $sub): ?Customer;

    /** Connects an existing account to a Google identity. */
    public function linkGoogle(int $id, string $sub): void;

    /** Inserts and returns the customer with its id. */
    public function create(Customer $customer): Customer;

    /** Saves name and contact details. */
    public function update(Customer $customer): void;

    public function updatePassword(int $id, string $hash): void;

    public function touchLogin(int $id): void;

    /** Stores a fresh (hashed) confirmation token. */
    public function setVerification(int $id, string $tokenHash, string $expires, string $sentAt): void;

    /** The customer whose unexpired confirmation token matches. */
    public function findByVerifyToken(string $tokenHash): ?Customer;

    public function markVerified(int $id): void;
}
