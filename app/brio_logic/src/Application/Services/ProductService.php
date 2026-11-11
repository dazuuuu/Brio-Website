<?php
declare(strict_types=1);

namespace Brio\Application\Services;

use Brio\Domain\Exceptions\NotFoundException;
use Brio\Domain\Product\Product;
use Brio\Domain\Product\ProductRepository;
use Brio\Infrastructure\Files\FileHandler;
use Brio\Infrastructure\Logging\Logger;

/** Product use-cases shared by the storefront, the public API and the admin. */
final class ProductService
{
    public function __construct(
        private readonly ProductRepository $repo,
        private readonly FileHandler $files,
        private readonly int $lowThreshold,
    ) {}

    public function lowThreshold(): int { return $this->lowThreshold; }

    /** Storefront listing: arrays shaped for templates. */
    public function catalog(string $category = 'all', string $q = '', ?bool $featured = null, ?bool $deal = null): array
    {
        $filters = ['category' => $category, 'q' => $q];
        if ($featured !== null) $filters['featured'] = $featured;
        if ($deal !== null) $filters['deal'] = $deal;
        return $this->toArrays($this->repo->search($filters));
    }

    /** Admin table: filters + sort + pagination. */
    public function paginate(array $query, int $perPage): array
    {
        $filters = [
            'q' => trim((string)($query['q'] ?? '')),
            'category' => (string)($query['category'] ?? 'all'),
            'stock' => in_array($query['stock'] ?? 'all', ['all', 'in', 'out', 'low'], true) ? ($query['stock'] ?? 'all') : 'all',
            'lowThreshold' => $this->lowThreshold,
        ];
        foreach (['featured', 'deal'] as $flag) {
            if (isset($query[$flag]) && $query[$flag] !== '') $filters[$flag] = filter_var($query[$flag], FILTER_VALIDATE_BOOLEAN);
        }
        $total = $this->repo->count($filters);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min($pages, max(1, (int)($query['page'] ?? 1)));
        $items = $this->repo->search($filters, [
            'sort' => (string)($query['sort'] ?? 'created'), 'dir' => (string)($query['dir'] ?? 'asc'),
            'limit' => $perPage, 'offset' => ($page - 1) * $perPage,
        ]);
        return ['data' => $this->toArrays($items), 'meta' => ['page' => $page, 'perPage' => $perPage, 'total' => $total, 'pages' => $pages]];
    }

    public function stats(): array
    {
        $s = $this->repo->stats($this->lowThreshold);
        $s['avgSales'] = $s['total'] ? (int)round($s['sold'] / $s['total']) : 0;
        $s['featured'] = $this->repo->count(['featured' => true]);
        $s['deals'] = $this->repo->count(['deal' => true]);
        return $s;
    }

    public function findBySlug(string $slug): ?array
    {
        $p = $this->repo->findBySlug($slug);
        return $p ? $p->toArray($this->lowThreshold) : null;
    }

    /** For pickers: [{id, name, category}] */
    public function options(): array
    {
        return array_map(static fn(Product $p) => ['id' => $p->id, 'name' => $p->name, 'category' => $p->category], $this->repo->search([], ['sort' => 'name']));
    }

    public function related(string $category, int $excludeId, int $limit = 4): array
    {
        $items = array_filter($this->repo->search(['category' => $category]), static fn(Product $p) => $p->id !== $excludeId);
        return $this->toArrays(array_slice(array_values($items), 0, $limit));
    }

    /** Dynamic product list for a page-builder "products" block. */
    public function forBlock(array $block): array
    {
        $limit = max(1, min(24, (int)($block['limit'] ?? 8)));
        $items = match ($block['source'] ?? 'featured') {
            'deals' => $this->repo->search(['deal' => true], ['limit' => $limit]),
            'latest' => $this->repo->search([], ['sort' => 'created', 'dir' => 'desc', 'limit' => $limit]),
            'category' => $this->repo->search(['category' => (string)($block['category'] ?? '')], ['limit' => $limit]),
            'selected' => $this->inOrder($block['ids'] ?? [], $limit),
            default => $this->repo->search(['featured' => true], ['limit' => $limit]),
        };
        return $this->toArrays($items);
    }

    /** @return Product[] products in the order the admin picked them */
    private function inOrder(array $ids, int $limit): array
    {
        $found = $this->repo->findMany($ids);
        $ordered = [];
        foreach ($ids as $id) if (isset($found[(int)$id])) $ordered[] = $found[(int)$id];
        return array_slice($ordered, 0, $limit);
    }

    public function get(int $id): array
    {
        return $this->find($id)->toArray($this->lowThreshold);
    }

    public function create(array $input, ?array $upload = null): array
    {
        $product = Product::fromInput($input);
        $product->slug = $this->repo->uniqueSlug($product->slug); // clean, unique /products/<slug> URL
        if ($upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $product->image = $this->files->storeImage($upload);
        }
        $this->repo->save($product);
        Logger::info('Product created', ['id' => $product->id, 'name' => $product->name]);
        return $product->toArray($this->lowThreshold);
    }

    public function update(int $id, array $input, ?array $upload = null): array
    {
        $existing = $this->find($id);
        $product = Product::fromInput($input, $existing);
        $product->slug = $existing->slug !== '' ? $existing->slug : $this->repo->uniqueSlug($product->slug, $id); // keep URLs stable when renaming
        if ($upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $old = $existing->image;
            $product->image = $this->files->storeImage($upload);
            $this->files->delete($old);
        }
        $this->repo->save($product);
        Logger::info('Product updated', ['id' => $id, 'fields' => array_keys($input)]);
        return $product->toArray($this->lowThreshold);
    }

    public function delete(int $id): void
    {
        $product = $this->find($id);
        $this->repo->delete($id);
        $this->files->delete($product->image);
        Logger::info('Product deleted', ['id' => $id, 'name' => $product->name]);
    }

    private function find(int $id): Product
    {
        return $this->repo->find($id) ?? throw new NotFoundException('Product not found.');
    }

    /** @param Product[] $products */
    private function toArrays(array $products): array
    {
        return array_map(fn(Product $p) => $p->toArray($this->lowThreshold), $products);
    }
}
