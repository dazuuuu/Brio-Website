<?php
// Search-engine and social-sharing helpers: titles, descriptions, Open Graph/Twitter cards, structured data,
// robots.txt, sitemap.xml and the web manifest. Everything is built from the store's own settings and products.

use Brio\Infrastructure\Config\Config;

/** Absolute address of a path inside the site, e.g. seo_abs('products/serum') -> https://shop.example/products/serum. */
function seo_abs(string $path = ''): string
{
    return rtrim(brio_public_url(), '/') . '/' . ltrim($path, '/');
}

/** The best-selling product with a photo (falls back to the first starter product before setup). */
function seo_popular_product(): ?array
{
    static $p = false;
    if ($p === false) {
        $p = Config::hasDatabase() ? site_try(static fn() => brio('products')->popular()) : null;
        if ($p === null) foreach (fallback_products('all', '', null, null) as $f) if ($f['image'] !== '') { $p = $f; break; }
    }
    return $p;
}

/**
 * The picture shown when a link is shared. A product page shares that product. Other pages share what the owner chose in
 * Settings: the best-selling product (default), the logo, or an uploaded image.
 */
function seo_share_image(?array $product = null): string
{
    if ($product !== null && ($product['image'] ?? '') !== '') return seo_abs($product['image']);
    $b = brand();
    if ($b['shareMode'] === 'custom' && $b['shareImagePath'] !== '') return seo_abs($b['shareImagePath']);
    if ($b['shareMode'] === 'popular' && ($popular = seo_popular_product()) !== null) return seo_abs($popular['image']);
    return seo_abs($b['ogLogo']);
}

function seo_default_description(): string
{
    $b = brand();
    return $b['name'] . ($b['tagline'] !== '' ? ' — ' . $b['tagline'] : '') . '. Browse our products and order online, with delivery to your door.';
}

/** Everything the <head> needs for one page. @param array{title?:string,description?:string,path?:string,product?:?array,noindex?:bool} $ctx */
function seo_context(array $ctx): array
{
    $b = brand();
    $product = $ctx['product'] ?? null;
    $path = trim((string)($ctx['path'] ?? ''), '/');
    return [
        'title' => $ctx['title'] ?? $b['name'],
        'description' => trim((string)($ctx['description'] ?? '')) !== '' ? mb_substr(trim((string)$ctx['description']), 0, 200) : seo_default_description(),
        'canonical' => seo_abs($path),
        'image' => seo_share_image($product),
        'type' => $product !== null ? 'product' : 'website',
        'noindex' => (bool)($ctx['noindex'] ?? false),
        'product' => $product,
    ];
}

/** Structured data (JSON-LD): the business on every page, plus the product on product pages. */
function seo_jsonld(array $seo): string
{
    $b = brand();
    $graph = [[
        '@type' => 'Organization', 'name' => $b['name'], 'url' => seo_abs(), 'logo' => seo_abs($b['ogLogo']),
    ], [
        '@type' => 'WebSite', 'name' => $b['name'], 'url' => seo_abs(),
    ]];
    if ($p = $seo['product']) {
        $graph[] = array_filter([
            '@type' => 'Product', 'name' => $p['name'], 'description' => short_description($p['description'] ?? '', 300) ?: null,
            'image' => $p['image'] !== '' ? [seo_abs($p['image'])] : null, 'sku' => (string)$p['id'], 'category' => $p['categoryLabel'] ?? null,
            'brand' => ['@type' => 'Brand', 'name' => $b['name']],
            'offers' => ['@type' => 'Offer', 'url' => $seo['canonical'], 'priceCurrency' => Config::get('app.currency_code', 'KES'), 'price' => (string)$p['price'],
                         'availability' => $p['stock'] > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'],
            'aggregateRating' => ($p['reviewCount'] ?? 0) > 0 ? ['@type' => 'AggregateRating', 'ratingValue' => (string)$p['rating'], 'reviewCount' => (string)$p['reviewCount']] : null,
        ], static fn($v) => $v !== null);
    }
    return json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
}

/** Icon file URL honouring a custom site icon (with a version so browsers refresh it). */
function seo_icon(string $file): string
{
    $b = brand();
    return url($b['iconBase'] . $file) . ($b['iconVersion'] !== '' ? '?v=' . rawurlencode($b['iconVersion']) : '');
}

/** robots.txt, sitemap.xml and the web manifest. */
function seo_serve(string $what): void
{
    $b = brand();
    if ($what === 'robots.txt') {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /api/\nDisallow: /setup\nDisallow: /checkout\nDisallow: /account\nDisallow: /login\nDisallow: /register\n\nSitemap: " . seo_abs('sitemap.xml') . "\n";
        return;
    }
    if ($what === 'site.webmanifest') {
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        $base = $b['iconBase'] . '';
        echo json_encode([
            'name' => $b['name'], 'short_name' => mb_substr($b['name'], 0, 12), 'description' => $b['tagline'], 'start_url' => './', 'scope' => './', 'display' => 'standalone',
            'background_color' => '#ffffff', 'theme_color' => '#00be62',
            'icons' => [['src' => $base . 'icon-192.png' . ($b['iconVersion'] !== '' ? '?v=' . $b['iconVersion'] : ''), 'sizes' => '192x192', 'type' => 'image/png'],
                        ['src' => $base . 'icon-512.png' . ($b['iconVersion'] !== '' ? '?v=' . $b['iconVersion'] : ''), 'sizes' => '512x512', 'type' => 'image/png']],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return;
    }
    // sitemap.xml: public pages and every product
    header('Content-Type: application/xml; charset=utf-8');
    $urls = [seo_abs('')];
    $private = ['login', 'register', 'account', 'checkout'];
    foreach (site_try(static fn() => brio('pages')->all(), []) as $pg) {
        if (!$pg['isPublished'] || in_array($pg['systemKey'], $private, true) || $pg['slug'] === '') continue;
        $urls[] = seo_abs($pg['slug']);
    }
    foreach (site_try(static fn() => brio('products')->slugs(), []) as $slug) $urls[] = seo_abs('products/' . $slug);
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach (array_unique($urls) as $u) echo '  <url><loc>' . htmlspecialchars($u, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc></url>\n";
    echo '</urlset>' . "\n";
}
