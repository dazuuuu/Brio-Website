<?php
declare(strict_types=1);

namespace Brio\Infrastructure\Persistence;

use Brio\Domain\Customer\Customer;
use Brio\Domain\Customer\CustomerRepository;
use Brio\Domain\Exceptions\ValidationException;
use PDO;

final class PdoCustomerRepository implements CustomerRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function find(int $id): ?Customer { return $this->one('SELECT * FROM customers WHERE id = ?', [$id]); }

    public function findByEmail(string $email): ?Customer { return $this->one('SELECT * FROM customers WHERE email = ?', [strtolower($email)]); }

    public function create(Customer $c): Customer
    {
        $now = date('Y-m-d H:i:s');
        try {
            $this->pdo->prepare('INSERT INTO customers (first_name, last_name, email, password_hash, phone, whatsapp, address, created_at) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$c->firstName, $c->lastName, $c->email, $c->passwordHash, $c->phone, $c->whatsapp, $c->address, $now]);
        } catch (\PDOException $e) {
            // Two people registering the same email at the same moment: the UNIQUE key catches the second.
            if ($e->getCode() === '23000') throw new ValidationException(['email' => ['An account with this email already exists. Try signing in.']]);
            throw $e;
        }
        $c->id = (int)$this->pdo->lastInsertId();
        $c->createdAt = $now;
        return $c;
    }

    public function update(Customer $c): void
    {
        $this->pdo->prepare('UPDATE customers SET first_name=?, last_name=?, phone=?, whatsapp=?, address=? WHERE id=?')
            ->execute([$c->firstName, $c->lastName, $c->phone, $c->whatsapp, $c->address, $c->id]);
    }

    public function updatePassword(int $id, string $hash): void
    {
        $this->pdo->prepare('UPDATE customers SET password_hash = ? WHERE id = ?')->execute([$hash, $id]);
    }

    public function touchLogin(int $id): void
    {
        $this->pdo->prepare('UPDATE customers SET last_login_at = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $id]);
    }

    private function one(string $sql, array $params): ?Customer
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $r = $st->fetch();
        return $r ? new Customer((int)$r['id'], $r['first_name'], $r['last_name'], $r['email'], $r['password_hash'], $r['phone'], $r['whatsapp'], $r['address'], $r['created_at']) : null;
    }
}
