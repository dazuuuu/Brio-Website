<?php
declare(strict_types=1);

namespace Brio\Domain\Admin;

final class Admin
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $passwordHash,
    ) {}

    public function verifyPassword(string $plain): bool
    {
        return password_verify($plain, $this->passwordHash);
    }
}
