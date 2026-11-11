<?php
// Storefront product access. Reads from the Brio backend (app/brio_logic) once setup has run;
// before that it falls back to the backend's seed file so the store still renders.

use Brio\Domain\Product\Product;
use Brio\Infrastructure\Config\Config;
use Brio\Infrastructure\Logging\Logger;

const LOW_STOCK_THRESHOLD = 10;

function categories(): array
{
    return Product::CATEGORIES;
}

/** @return array<int, array> products shaped like Product::toArray() */
function filter_products(string $category = 'all', string $q = '', ?bool $featured = null, ?bool $deal = null): array
{
    if (Config::hasDatabase()) {
        try {
            return brio('products')->catalog($category, $q, $featured, $deal);
        } catch (Throwable $e) {
            Logger::error('Storefront could not read products', ['error' => $e->getMessage()]);
        }
    }
    return fallback_products($category, $q, $featured, $deal);
}

function get_products(): array
{
    return filter_products();
}

function fallback_products(string $category, string $q, ?bool $featured, ?bool $deal): array
{
    static $all = null;
    if ($all === null) {
        $all = [];
        foreach (require BRIO_ROOT . '/database/seeds/products.php' as $i => $row) {
            $p = Product::fromInput($row);
            $p->id = $i + 1;
            $p->image = $row['image'];
            $all[] = $p->toArray(LOW_STOCK_THRESHOLD);
        }
    }
    $q = mb_strtolower(trim($q));
    return array_values(array_filter($all, static fn($p) =>
        ($category === 'all' || $p['category'] === $category)
        && ($featured === null || $p['isFeatured'] === $featured)
        && ($deal === null || $p['isDeal'] === $deal)
        && ($q === '' || str_contains(mb_strtolower($p['name'] . ' ' . $p['description']), $q))));
}

/** Never trust the URL: anything unknown falls back to "all". */
function parse_category(?string $v): string
{
    return isset(categories()[(string)$v]) ? (string)$v : 'all';
}

function stock_status(int $stock): array
{
    if ($stock <= 0) return ['Out of stock', 'out'];
    if ($stock <= LOW_STOCK_THRESHOLD) return ["Only $stock left", 'low'];
    return ['In stock', 'in'];
}

/** Public URL for a product image path stored relative to /public. */
function product_image(array $p): string
{
    return $p['image'] !== '' ? url($p['image']) : asset('img/favicon.svg');
}

/** One-line plain-text summary of a description (for the cart), cut at a word boundary. */
function short_description(string $text, int $max = 140): string
{
    $text = trim((string)preg_replace('/\s+/', ' ', strip_tags($text)));
    if (mb_strlen($text) <= $max) return $text;
    $cut = mb_substr($text, 0, $max);
    $space = mb_strrpos($cut, ' ');
    return rtrim($space !== false && $space > $max * 0.6 ? mb_substr($cut, 0, $space) : $cut, " ,.;:-") . '…';
}

/** JSON the cart script snapshots when an item is added. */
function product_json(array $p): string
{
    return e(json_encode([
        'id' => $p['id'], 'name' => $p['name'], 'category' => $p['category'], 'description' => short_description($p['description'] ?? ''),
        'price' => $p['price'], 'image' => product_image($p), 'stock' => $p['stock'],
    ], JSON_UNESCAPED_UNICODE));
}
