<?php
declare(strict_types=1);

namespace Brio\Domain\Navigation;

interface NavRepository
{
    /** @return NavItem[] ordered */
    public function byLocation(string $location, bool $activeOnly = false): array;

    /** Replaces a whole menu atomically. @param NavItem[] $items */
    public function replace(string $location, array $items): void;
}
