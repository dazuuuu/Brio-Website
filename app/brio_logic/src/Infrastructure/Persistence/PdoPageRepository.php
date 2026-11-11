<?php
declare(strict_types=1);

namespace Brio\Infrastructure\Persistence;

use Brio\Domain\Page\Page;
use Brio\Domain\Page\PageRepository;
use PDO;

final class PdoPageRepository implements PageRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function all(): array
    {
        return array_map($this->hydrate(...), $this->pdo->query('SELECT * FROM pages ORDER BY updated_at DESC, id DESC')->fetchAll());
    }

    public function find(int $id): ?Page
    {
        return $this->one('SELECT * FROM pages WHERE id = ?', [$id]);
    }

    public function findBySlug(string $slug): ?Page
    {
        return $this->one('SELECT * FROM pages WHERE slug = ?', [$slug]);
    }

    public function findBySystemKey(string $key): ?Page
    {
        return $this->one('SELECT * FROM pages WHERE system_key = ?', [$key]);
    }

    public function saveSystem(Page $p): Page
    {
        $this->pdo->prepare('INSERT INTO pages (title, slug, blocks, meta_description, is_published, system_key) VALUES (?,?,?,?,?,?)')
            ->execute([$p->title, $p->slug, json_encode($p->blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), $p->metaDescription, (int)$p->isPublished, $p->systemKey]);
        $p->id = (int)$this->pdo->lastInsertId();
        return $p;
    }

    public function save(Page $p): Page
    {
        $blocks = json_encode($p->blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if ($p->id === null) {
            $this->pdo->prepare('INSERT INTO pages (title, slug, blocks, meta_description, is_published) VALUES (?,?,?,?,?)')
                ->execute([$p->title, $p->slug, $blocks, $p->metaDescription, (int)$p->isPublished]);
            $p->id = (int)$this->pdo->lastInsertId();
        } else {
            $this->pdo->prepare('UPDATE pages SET title=?, slug=?, blocks=?, meta_description=?, is_published=? WHERE id=?')
                ->execute([$p->title, $p->slug, $blocks, $p->metaDescription, (int)$p->isPublished, $p->id]);
        }
        return $p;
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM pages WHERE id = ?')->execute([$id]);
    }

    private function one(string $sql, array $params): ?Page
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    private function hydrate(array $r): Page
    {
        return new Page((int)$r['id'], $r['title'], $r['slug'], json_decode($r['blocks'], true) ?: [], $r['meta_description'], (bool)$r['is_published'], $r['updated_at'], $r['system_key']);
    }
}
