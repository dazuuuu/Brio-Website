<?php
declare(strict_types=1);

namespace Brio\Application\Services;

use Brio\Domain\Link\Link;
use Brio\Domain\Page\PageRepository;
use Brio\Domain\Product\ProductRepository;

/**
 * Turns a stored Link into the URL to show today. Returns an app-relative path with no leading slash
 * ("products/vitamin-c-e-serum", "about-us", "" for home) or an absolute URL (https:, mailto:, tel:).
 * Returns null when the target no longer exists (deleted product, unpublished page) so callers can hide the link.
 */
final class LinkResolver
{
    /** @var array<string,?string> */
    private array $cache = [];

    public function __construct(private readonly ProductRepository $products, private readonly PageRepository $pages) {}

    public function resolve(Link $link): ?string
    {
        $key = $link->type . ':' . $link->target;
        if (array_key_exists($key, $this->cache)) return $this->cache[$key];

        return $this->cache[$key] = match ($link->type) {
            'home' => '',
            'shop' => 'products',
            'category' => 'products?category=' . rawurlencode((string)$link->target),
            'product' => ($p = $this->products->find((int)$link->target)) ? 'products/' . $p->slug : null,
            'page' => ($pg = $this->pages->find((int)$link->target)) && $pg->isPublished ? $pg->slug : null,
            'url' => str_starts_with((string)$link->target, '/') ? ltrim((string)$link->target, '/') : $link->target,
            default => null,
        };
    }
}
