<?php
declare(strict_types=1);

namespace Brio\Domain\Hero;

interface HeroSlideRepository
{
    /** @return HeroSlide[] ordered by sort_order */
    public function all(bool $activeOnly = false): array;

    public function find(int $id): ?HeroSlide;

    /** Inserts at the end when new. */
    public function save(HeroSlide $slide): HeroSlide;

    public function delete(int $id): void;

    /** @param int[] $ids slide ids in their new order */
    public function reorder(array $ids): void;
}
