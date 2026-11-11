<?php
declare(strict_types=1);

namespace Brio\Infrastructure\Persistence;

use Brio\Domain\Hero\HeroSlide;
use Brio\Domain\Hero\HeroSlideRepository;
use Brio\Domain\Link\Link;
use PDO;

final class PdoHeroSlideRepository implements HeroSlideRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM hero_slides' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY sort_order, id';
        return array_map($this->hydrate(...), $this->pdo->query($sql)->fetchAll());
    }

    public function find(int $id): ?HeroSlide
    {
        $st = $this->pdo->prepare('SELECT * FROM hero_slides WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function save(HeroSlide $s): HeroSlide
    {
        $data = [$s->image, $s->mobileImage, $s->alt, $s->ctaLabel, $s->link->type, $s->link->target, $s->ctaColor, (int)$s->isActive];
        if ($s->id === null) {
            $s->sortOrder = (int)$this->pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM hero_slides')->fetchColumn();
            $this->pdo->prepare('INSERT INTO hero_slides (image, mobile_image, alt, cta_label, link_type, link_target, cta_color, is_active, sort_order) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([...$data, $s->sortOrder]);
            $s->id = (int)$this->pdo->lastInsertId();
        } else {
            $this->pdo->prepare('UPDATE hero_slides SET image=?, mobile_image=?, alt=?, cta_label=?, link_type=?, link_target=?, cta_color=?, is_active=? WHERE id=?')
                ->execute([...$data, $s->id]);
        }
        return $s;
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM hero_slides WHERE id = ?')->execute([$id]);
    }

    public function reorder(array $ids): void
    {
        $this->pdo->beginTransaction();
        $st = $this->pdo->prepare('UPDATE hero_slides SET sort_order = ? WHERE id = ?');
        foreach (array_values($ids) as $position => $id) $st->execute([$position + 1, (int)$id]);
        $this->pdo->commit();
    }

    private function hydrate(array $r): HeroSlide
    {
        return new HeroSlide((int)$r['id'], $r['image'], $r['mobile_image'], $r['alt'], $r['cta_label'],
            new Link($r['link_type'], $r['link_target']), $r['cta_color'], (int)$r['sort_order'], (bool)$r['is_active']);
    }
}
