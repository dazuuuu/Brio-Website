<?php
declare(strict_types=1);

namespace Brio\Infrastructure\Persistence;

use Brio\Domain\Link\Link;
use Brio\Domain\Navigation\NavItem;
use Brio\Domain\Navigation\NavRepository;
use PDO;

final class PdoNavRepository implements NavRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function byLocation(string $location, bool $activeOnly = false): array
    {
        $st = $this->pdo->prepare('SELECT * FROM nav_items WHERE location = ?' . ($activeOnly ? ' AND is_active = 1' : '') . ' ORDER BY sort_order, id');
        $st->execute([$location]);
        return array_map(static fn(array $r) => new NavItem((int)$r['id'], $r['location'], $r['label'], new Link($r['link_type'], $r['link_target']),
            (bool)$r['new_tab'], (bool)$r['is_active'], (int)$r['sort_order']), $st->fetchAll());
    }

    public function replace(string $location, array $items): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('DELETE FROM nav_items WHERE location = ?')->execute([$location]);
            $st = $this->pdo->prepare('INSERT INTO nav_items (location, label, link_type, link_target, new_tab, is_active, sort_order) VALUES (?,?,?,?,?,?,?)');
            foreach (array_values($items) as $i => $it) {
                $st->execute([$location, $it->label, $it->link->type, $it->link->target, (int)$it->newTab, (int)$it->isActive, $i + 1]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
