<?php
declare(strict_types=1);

namespace Brio\Application\Services;

use Brio\Domain\Exceptions\ValidationException;
use Brio\Domain\Navigation\NavItem;
use Brio\Domain\Navigation\NavRepository;
use Brio\Infrastructure\Logging\Logger;

final class NavigationService
{
    private const MAX_ITEMS = 20;

    public function __construct(private readonly NavRepository $nav, private readonly LinkResolver $links) {}

    /** Admin editor: every item, including inactive ones. */
    public function items(string $location): array
    {
        $this->assertLocation($location);
        return array_map(static fn(NavItem $i) => $i->toArray(), $this->nav->byLocation($location));
    }

    /** Storefront: active items whose targets still exist, with ready-to-use hrefs. */
    public function resolved(string $location): array
    {
        $out = [];
        foreach ($this->nav->byLocation($location, true) as $i) {
            $href = $this->links->resolve($i->link);
            if ($href === null) continue;
            $out[] = ['label' => $i->label, 'href' => $href, 'type' => $i->link->type, 'target' => $i->link->target, 'newTab' => $i->newTab];
        }
        return $out;
    }

    public function save(string $location, mixed $items): array
    {
        $this->assertLocation($location);
        if (!is_array($items)) throw new ValidationException(['items' => ['Send the menu items as a list.']]);
        if (count($items) > self::MAX_ITEMS) throw new ValidationException(['items' => ['A menu can have up to ' . self::MAX_ITEMS . ' items.']]);

        $clean = [];
        foreach (array_values($items) as $i => $row) $clean[] = NavItem::fromInput($location, is_array($row) ? $row : [], $i);
        $this->nav->replace($location, $clean);
        Logger::info('Navigation saved', ['location' => $location, 'items' => count($clean)]);
        return $this->items($location);
    }

    private function assertLocation(string $location): void
    {
        if (!in_array($location, NavItem::LOCATIONS, true)) throw new ValidationException(['location' => ['Unknown menu.']]);
    }
}
