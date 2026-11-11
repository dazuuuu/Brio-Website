<?php
declare(strict_types=1);

namespace Brio\Domain\Link;

use Brio\Domain\Exceptions\ValidationException;
use Brio\Domain\Product\Product;

/**
 * Where a button or menu item points. One value object shared by hero slides,
 * navigation items and page-builder buttons.
 *
 *  none      no link (hero: no button)
 *  home      the home page
 *  shop      the full shop page
 *  category  a category slug      → /products?category=skin-care
 *  product   a product id         → /products/<slug>
 *  page      a page id            → /<page slug>
 *  url       custom URL: https://…, /path, mailto:, tel:
 */
final class Link
{
    public const TYPES = ['none', 'home', 'shop', 'category', 'product', 'page', 'url'];

    public function __construct(public readonly string $type = 'none', public readonly ?string $target = null) {}

    public static function none(): self { return new self(); }

    public function isNone(): bool { return $this->type === 'none'; }

    /** @throws ValidationException keyed by $field */
    public static function fromInput(mixed $type, mixed $target, string $field = 'link'): self
    {
        $type = is_string($type) ? trim($type) : 'none';
        $target = is_scalar($target) ? trim((string)$target) : '';
        $fail = static fn(string $m) => new ValidationException([$field => [$m]]);

        if (!in_array($type, self::TYPES, true)) throw $fail('Choose a valid link type.');

        return match ($type) {
            'category' => isset(Product::CATEGORIES[$target]) ? new self($type, $target) : throw $fail('Choose a category.'),
            'product', 'page' => ctype_digit($target) && (int)$target > 0 ? new self($type, (string)(int)$target) : throw $fail("Choose a $type."),
            'url' => ($u = self::cleanUrl($target)) !== null ? new self($type, $u) : throw $fail('Enter a link starting with https://, /, mailto: or tel:.'),
            default => new self($type),
        };
    }

    /** Accepts https://…, /root-relative, mailto:, tel:. Rejects javascript:, data:, whitespace, quotes. */
    public static function cleanUrl(string $u): ?string
    {
        $u = trim($u);
        if ($u === '' || strlen($u) > 255 || preg_match('/[\x00-\x20<>"\']/', $u)) return null;
        $ok = preg_match('#^https?://[^/]+#i', $u) || preg_match('#^/(?!/)#', $u) || preg_match('/^(mailto|tel):/i', $u);
        return $ok ? $u : null;
    }

    public function toArray(): array { return ['type' => $this->type, 'target' => $this->target]; }
}
