<?php
declare(strict_types=1);

namespace Brio\Domain\Customer;

interface CustomerRepository
{
    public function find(int $id): ?Customer;

    public function findByEmail(string $email): ?Customer;

    /** Inserts and returns the customer with its id. */
    public function create(Customer $customer): Customer;

    /** Saves name and contact details. */
    public function update(Customer $customer): void;

    public function updatePassword(int $id, string $hash): void;

    public function touchLogin(int $id): void;
}
