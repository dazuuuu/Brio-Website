<?php
declare(strict_types=1);

namespace Brio\Domain\Product;

/** Persistence contract; implemented in Infrastructure\Persistence. */
interface ProductRepository
{
    public function find(int $id): ?Product;

    public function findBySlug(string $slug): ?Product;

    /** @param int[] $ids @return array<int,Product> keyed by id */
    public function findMany(array $ids): array;

    /** Returns $slug, or $slug-2, $slug-3 … so product URLs stay unique. */
    public function uniqueSlug(string $slug, ?int $excludeId = null): string;

    /**
     * @param array{category?:string,q?:string,featured?:bool,deal?:bool,stock?:string,lowThreshold?:int} $filters
     * @param array{sort?:string,dir?:string,limit?:int,offset?:int} $options
     * @return Product[]
     */
    public function search(array $filters = [], array $options = []): array;

    public function count(array $filters = []): int;

    /** @return array{total:int,revenue:float,sold:int,in:int,out:int,low:int} */
    public function stats(int $lowThreshold): array;

    public function save(Product $product): Product;

    public function delete(int $id): void;

    /** Removes every product and restarts numbering. @return array{count:int,images:string[]} what was removed */
    public function deleteAll(): array;
}
