<?php
declare(strict_types=1);

namespace Brio\Domain\Page;

interface PageRepository
{
    /** @return Page[] newest first */
    public function all(): array;

    public function find(int $id): ?Page;

    public function findBySlug(string $slug): ?Page;

    public function findBySystemKey(string $key): ?Page;

    /** Insert a built-in page with its system key (used by seeding/restore). */
    public function saveSystem(Page $page): Page;

    public function save(Page $page): Page;

    public function delete(int $id): void;
}
