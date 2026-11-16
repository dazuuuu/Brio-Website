<?php
declare(strict_types=1);

namespace Brio\Infrastructure\Persistence;

use Brio\Domain\Ad\Ad;
use Brio\Domain\Ad\AdRepository;
use Brio\Domain\Link\Link;
use PDO;

final class PdoAdRepository implements AdRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function all(): array
    {
        return array_map($this->hydrate(...), $this->pdo->query('SELECT * FROM ads ORDER BY sort_order, id')->fetchAll());
    }

    public function find(int $id): ?Ad
    {
        $st = $this->pdo->prepare('SELECT * FROM ads WHERE id = ?');
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ? $this->hydrate($r) : null;
    }

    public function save(Ad $a): Ad
    {
        $data = [$a->name, $a->kind, $a->headline, $a->body, $a->productId, $a->offerText, $a->offerPrice, $a->mediaType, $a->mediaPath, $a->ctaLabel,
                 $a->link->type, $a->link->target, $a->frequency, $a->delaySeconds, $a->pages, $a->startsAt, $a->endsAt, (int)$a->isActive];
        if ($a->id === null) {
            $a->sortOrder = (int)$this->pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM ads')->fetchColumn();
            $a->createdAt = date('Y-m-d H:i:s');
            $this->pdo->prepare('INSERT INTO ads (name, kind, headline, body, product_id, offer_text, offer_price, media_type, media_path, cta_label, link_type, link_target, frequency, delay_seconds, pages, starts_at, ends_at, is_active, sort_order, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([...$data, $a->sortOrder, $a->createdAt]);
            $a->id = (int)$this->pdo->lastInsertId();
        } else {
            $this->pdo->prepare('UPDATE ads SET name=?, kind=?, headline=?, body=?, product_id=?, offer_text=?, offer_price=?, media_type=?, media_path=?, cta_label=?, link_type=?, link_target=?, frequency=?, delay_seconds=?, pages=?, starts_at=?, ends_at=?, is_active=? WHERE id=?')
                ->execute([...$data, $a->id]);
        }
        return $a;
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM ads WHERE id = ?')->execute([$id]);
    }

    public function reorder(array $ids): void
    {
        $this->pdo->beginTransaction();
        $st = $this->pdo->prepare('UPDATE ads SET sort_order = ? WHERE id = ?');
        foreach (array_values($ids) as $i => $id) $st->execute([$i + 1, (int)$id]);
        $this->pdo->commit();
    }

    public function increment(int $id, string $counter): void
    {
        $col = $counter === 'clicks' ? 'clicks' : 'views'; // whitelist: never interpolate input
        $this->pdo->prepare("UPDATE ads SET $col = $col + 1 WHERE id = ?")->execute([$id]);
    }

    private function hydrate(array $r): Ad
    {
        return new Ad((int)$r['id'], $r['name'], $r['kind'], $r['headline'], $r['body'], $r['product_id'] !== null ? (int)$r['product_id'] : null, $r['offer_text'],
            $r['offer_price'] !== null ? (float)$r['offer_price'] : null, $r['media_type'], $r['media_path'], $r['cta_label'], new Link($r['link_type'], $r['link_target']),
            $r['frequency'], (int)$r['delay_seconds'], $r['pages'], $r['starts_at'], $r['ends_at'], (bool)$r['is_active'], (int)$r['sort_order'], (int)$r['views'],
            (int)$r['clicks'], $r['created_at']);
    }
}
