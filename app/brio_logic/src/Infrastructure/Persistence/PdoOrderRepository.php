<?php
declare(strict_types=1);

namespace Brio\Infrastructure\Persistence;

use Brio\Domain\Order\Order;
use Brio\Domain\Order\OrderRepository;
use PDO;

final class PdoOrderRepository implements OrderRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function find(int $id): ?Order
    {
        $st = $this->pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function save(Order $o): Order
    {
        $this->pdo->prepare('INSERT INTO orders (customer_name, phone, whatsapp, address, notes, channel, items, total, status, ip, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([$o->name, $o->phone, $o->whatsapp, $o->address, $o->notes, $o->channel,
                json_encode($o->items, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $o->total, $o->status, $o->ip, $o->createdAt]);
        $o->id = (int)$this->pdo->lastInsertId();
        $o->orderNumber = 'BRIO-' . (5000 + $o->id);
        $this->pdo->prepare('UPDATE orders SET order_number = ? WHERE id = ?')->execute([$o->orderNumber, $o->id]);
        return $o;
    }

    public function search(?string $status, int $limit, int $offset): array
    {
        $sql = 'SELECT * FROM orders' . ($status ? ' WHERE status = ?' : '') . ' ORDER BY id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);
        $st = $this->pdo->prepare($sql);
        $st->execute($status ? [$status] : []);
        return array_map($this->hydrate(...), $st->fetchAll());
    }

    public function count(?string $status): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM orders' . ($status ? ' WHERE status = ?' : ''));
        $st->execute($status ? [$status] : []);
        return (int)$st->fetchColumn();
    }

    public function countsByStatus(): array
    {
        return array_map('intval', $this->pdo->query('SELECT status, COUNT(*) FROM orders GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR) ?: []);
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([$id]);
    }

    public function deleteAll(): int
    {
        $count = (int)$this->pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $this->pdo->exec('DELETE FROM orders');
        $this->pdo->exec('ALTER TABLE orders AUTO_INCREMENT = 1');
        return $count;
    }

    public function countRecent(string $ip, int $seconds): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM orders WHERE ip = ? AND created_at >= ?');
        $st->execute([$ip, date('Y-m-d H:i:s', time() - $seconds)]);
        return (int)$st->fetchColumn();
    }

    private function hydrate(array $r): Order
    {
        return new Order((int)$r['id'], $r['order_number'], $r['customer_name'], $r['phone'], $r['whatsapp'], $r['address'], $r['notes'],
            $r['channel'], json_decode($r['items'], true) ?: [], (float)$r['total'], $r['status'], $r['ip'], $r['created_at']);
    }
}
