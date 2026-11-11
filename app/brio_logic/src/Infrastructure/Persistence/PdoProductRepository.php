<?php
declare(strict_types=1);

namespace Brio\Infrastructure\Persistence;

use Brio\Domain\Product\Product;
use Brio\Domain\Product\ProductRepository;
use PDO;

final class PdoProductRepository implements ProductRepository
{
    /** Whitelisted sort keys -> SQL columns (never interpolate user input). */
    private const SORTS = [
        'name' => 'name', 'price' => 'price', 'sales' => 'sales', 'revenue' => '(price * sales)',
        'stock' => 'stock', 'rating' => 'rating', 'created' => 'id',
    ];

    public function __construct(private readonly PDO $pdo) {}

    public function find(int $id): ?Product
    {
        $st = $this->pdo->prepare('SELECT * FROM products WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function findBySlug(string $slug): ?Product
    {
        $st = $this->pdo->prepare('SELECT * FROM products WHERE slug = ? ORDER BY id LIMIT 1');
        $st->execute([$slug]);
        $row = $st->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function findMany(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($i) => $i > 0)));
        if (!$ids) return [];
        $st = $this->pdo->prepare('SELECT * FROM products WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
        $st->execute($ids);
        $out = [];
        foreach ($st->fetchAll() as $row) $out[(int)$row['id']] = $this->hydrate($row);
        return $out;
    }

    public function uniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $candidate = $slug;
        for ($n = 2; $n < 500; $n++) {
            $st = $this->pdo->prepare('SELECT 1 FROM products WHERE slug = ? AND id <> ? LIMIT 1');
            $st->execute([$candidate, $excludeId ?? 0]);
            if (!$st->fetchColumn()) return $candidate;
            $candidate = "$slug-$n";
        }
        return $slug . '-' . bin2hex(random_bytes(3));
    }

    public function search(array $filters = [], array $options = []): array
    {
        [$where, $params] = $this->where($filters);
        $col = self::SORTS[$options['sort'] ?? 'created'] ?? 'id';
        $dir = strtolower($options['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
        $sql = "SELECT * FROM products $where ORDER BY $col $dir, id ASC";
        if (isset($options['limit'])) {
            $sql .= ' LIMIT ' . max(1, (int)$options['limit']) . ' OFFSET ' . max(0, (int)($options['offset'] ?? 0));
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return array_map($this->hydrate(...), $st->fetchAll());
    }

    public function count(array $filters = []): int
    {
        [$where, $params] = $this->where($filters);
        $st = $this->pdo->prepare("SELECT COUNT(*) FROM products $where");
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    public function stats(int $lowThreshold): array
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) total, COALESCE(SUM(price * sales), 0) revenue, COALESCE(SUM(sales), 0) sold,
            SUM(stock > ?) in_stock, SUM(stock = 0) out_stock, SUM(stock > 0 AND stock <= ?) low_stock FROM products');
        $st->execute([$lowThreshold, $lowThreshold]);
        $r = $st->fetch();
        return ['total' => (int)$r['total'], 'revenue' => (float)$r['revenue'], 'sold' => (int)$r['sold'],
                'in' => (int)$r['in_stock'], 'out' => (int)$r['out_stock'], 'low' => (int)$r['low_stock']];
    }

    public function save(Product $p): Product
    {
        $data = [
            'name' => $p->name, 'slug' => $p->slug, 'description' => $p->description, 'category' => $p->category,
            'price' => $p->price, 'compare_at_price' => $p->compareAtPrice, 'image' => $p->image, 'rating' => $p->rating,
            'review_count' => $p->reviewCount, 'stock' => $p->stock, 'sales' => $p->sales,
            'is_featured' => (int)$p->isFeatured, 'is_deal' => (int)$p->isDeal, 'badge' => $p->badge,
            'deal_eyebrow' => $p->dealEyebrow, 'deal_tag' => $p->dealTag, 'deal_tone' => $p->dealTone,
        ];
        if ($p->id === null) {
            $cols = implode(', ', array_keys($data));
            $marks = implode(', ', array_fill(0, count($data), '?'));
            $this->pdo->prepare("INSERT INTO products ($cols) VALUES ($marks)")->execute(array_values($data));
            $p->id = (int)$this->pdo->lastInsertId();
        } else {
            $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
            $this->pdo->prepare("UPDATE products SET $set WHERE id = ?")->execute([...array_values($data), $p->id]);
        }
        return $p;
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    }

    public function deleteAll(): array
    {
        $images = array_values(array_filter($this->pdo->query("SELECT image FROM products WHERE image <> ''")->fetchAll(PDO::FETCH_COLUMN)));
        $count = (int)$this->pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $this->pdo->exec('DELETE FROM products');
        $this->pdo->exec('ALTER TABLE products AUTO_INCREMENT = 1');
        return ['count' => $count, 'images' => $images];
    }

    /** @return array{0:string,1:array} */
    private function where(array $f): array
    {
        $sql = [];
        $params = [];
        if (!empty($f['category']) && $f['category'] !== 'all') { $sql[] = 'category = ?'; $params[] = $f['category']; }
        if (isset($f['featured'])) { $sql[] = 'is_featured = ?'; $params[] = (int)$f['featured']; }
        if (isset($f['deal'])) { $sql[] = 'is_deal = ?'; $params[] = (int)$f['deal']; }
        if (!empty($f['q'])) {
            $sql[] = '(name LIKE ? OR description LIKE ?)';
            $like = '%' . addcslashes((string)$f['q'], '%_\\') . '%';
            array_push($params, $like, $like);
        }
        $low = (int)($f['lowThreshold'] ?? 10);
        match ($f['stock'] ?? 'all') {
            'in' => [$sql[] = 'stock > ?', $params[] = $low],
            'out' => $sql[] = 'stock = 0',
            'low' => [$sql[] = 'stock > 0 AND stock <= ?', $params[] = $low],
            default => null,
        };
        return [$sql ? 'WHERE ' . implode(' AND ', $sql) : '', $params];
    }

    private function hydrate(array $r): Product
    {
        return new Product(
            (int)$r['id'], $r['name'], $r['slug'], $r['description'], $r['category'], (float)$r['price'],
            $r['compare_at_price'] === null ? null : (float)$r['compare_at_price'], $r['image'], (float)$r['rating'],
            (int)$r['review_count'], (int)$r['stock'], (int)$r['sales'], (bool)$r['is_featured'], (bool)$r['is_deal'],
            $r['badge'], $r['deal_eyebrow'], $r['deal_tag'], $r['deal_tone'],
        );
    }
}
