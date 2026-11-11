<?php
declare(strict_types=1);

namespace Brio\Domain\Order;

interface OrderRepository
{
    public function find(int $id): ?Order;

    /** Inserts, assigns the id and the BRIO-#### number. */
    public function save(Order $order): Order;

    /** @return Order[] newest first */
    public function search(?string $status, int $limit, int $offset): array;

    public function count(?string $status): int;

    /** @return array<string,int> status => number of orders */
    public function countsByStatus(): array;

    public function updateStatus(int $id, string $status): void;

    public function delete(int $id): void;

    /** Removes every order and restarts numbering at BRIO-5001. @return int how many were removed */
    public function deleteAll(): int;

    /** Orders from one IP in the last $seconds (spam guard). */
    public function countRecent(string $ip, int $seconds): int;
}
