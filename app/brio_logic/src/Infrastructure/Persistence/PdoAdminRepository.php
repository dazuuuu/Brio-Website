<?php
declare(strict_types=1);

namespace Brio\Infrastructure\Persistence;

use Brio\Domain\Admin\Admin;
use Brio\Domain\Admin\AdminRepository;
use PDO;

final class PdoAdminRepository implements AdminRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function findByEmail(string $email): ?Admin
    {
        return $this->one('SELECT * FROM admins WHERE email = ?', [strtolower($email)]);
    }

    public function find(int $id): ?Admin
    {
        return $this->one('SELECT * FROM admins WHERE id = ?', [$id]);
    }

    public function exists(): bool
    {
        return (bool)$this->pdo->query('SELECT 1 FROM admins LIMIT 1')->fetchColumn();
    }

    public function create(Admin $a): Admin
    {
        $this->pdo->prepare('INSERT INTO admins (name, email, password_hash) VALUES (?, ?, ?)')
            ->execute([$a->name, strtolower($a->email), $a->passwordHash]);
        return new Admin((int)$this->pdo->lastInsertId(), $a->name, strtolower($a->email), $a->passwordHash);
    }

    public function touchLogin(int $id): void
    {
        $this->pdo->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = ?')->execute([$id]);
    }

    private function one(string $sql, array $params): ?Admin
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $r = $st->fetch();
        return $r ? new Admin((int)$r['id'], $r['name'], $r['email'], $r['password_hash']) : null;
    }
}
