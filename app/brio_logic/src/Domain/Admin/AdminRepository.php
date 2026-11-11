<?php
declare(strict_types=1);

namespace Brio\Domain\Admin;

interface AdminRepository
{
    public function findByEmail(string $email): ?Admin;

    public function find(int $id): ?Admin;

    public function exists(): bool;

    public function create(Admin $admin): Admin;

    public function touchLogin(int $id): void;
}
