<?php
declare(strict_types=1);

namespace Brio\Domain\Ad;

interface AdRepository
{
    /** @return Ad[] in display order */
    public function all(): array;

    public function find(int $id): ?Ad;

    /** Inserts at the end when new. */
    public function save(Ad $ad): Ad;

    public function delete(int $id): void;

    /** @param int[] $ids */
    public function reorder(array $ids): void;

    /** @param 'views'|'clicks' $counter */
    public function increment(int $id, string $counter): void;
}
