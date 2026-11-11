<?php
declare(strict_types=1);

namespace Brio\Domain\Page;

use Brio\Domain\Exceptions\ValidationException;

/** A page built from blocks, served at its clean URL (/about-us, /help/shipping …). */
final class Page
{
    /** First URL segments owned by the app; pages can't use them. */
    public const RESERVED = ['admin', 'api', 'setup', 'assets', 'uploads', 'products', 'login', 'register', 'account', 'checkout',
                             'index.php', 'favicon.ico', 'robots.txt', 'sitemap.xml'];

    public function __construct(
        public ?int $id,
        public string $title,
        public string $slug,
        public array $blocks,
        public ?string $metaDescription,
        public bool $isPublished,
        public ?string $updatedAt = null,
        /** Set for the store's built-in pages (home, products, login …). Their URL can't change. */
        public ?string $systemKey = null,
    ) {}

    public static function fromInput(array $in, ?self $existing = null): self
    {
        $e = [];
        $title = trim((string)($in['title'] ?? $existing?->title ?? ''));
        if ($title === '' || mb_strlen($title) > 160) $e['title'][] = 'Enter a page title (max 160 characters).';

        if ($existing?->systemKey !== null) {
            $slug = $existing->slug; // built-in pages keep their URL
        } else {
            $rawSlug = array_key_exists('slug', $in) ? (string)$in['slug'] : ($existing?->slug ?? '');
            $slug = self::normalizeSlug($rawSlug !== '' ? $rawSlug : $title);
            if ($slug === '' || strlen($slug) > 120) {
                $e['slug'][] = 'Enter a URL using letters, numbers and dashes.';
            } elseif (in_array(explode('/', $slug)[0], self::RESERVED, true)) {
                $e['slug'][] = '“' . explode('/', $slug)[0] . '” is used by the store. Choose a different URL.';
            }
        }

        $meta = array_key_exists('metaDescription', $in) ? trim((string)$in['metaDescription']) : (string)($existing?->metaDescription ?? '');
        if (mb_strlen($meta) > 300) $e['metaDescription'][] = 'Keep the description under 300 characters.';

        $blocks = $existing?->blocks ?? [];
        if (array_key_exists('blocks', $in)) {
            try { $blocks = Blocks::sanitize($in['blocks']); } catch (ValidationException $x) { $e += $x->errors; }
        }
        if ($e) throw new ValidationException($e, ($e['blocks'][0] ?? null) ?: 'Please fix the highlighted fields.');

        $published = isset($in['isPublished']) ? filter_var($in['isPublished'], FILTER_VALIDATE_BOOLEAN) : ($existing?->isPublished ?? true);
        return new self($existing?->id, $title, $slug, $blocks, $meta !== '' ? $meta : null, $published, null, $existing?->systemKey);
    }

    /** "About Us!" → "about-us"; "Help / Shipping" → "help/shipping". */
    public static function normalizeSlug(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('#[^a-z0-9/]+#', '-', $s) ?? '';
        $segments = array_filter(array_map(static fn($seg) => trim($seg, '-'), explode('/', $s)), static fn($seg) => $seg !== '');
        return implode('/', $segments);
    }

    public function toArray(): array
    {
        return ['id' => $this->id, 'title' => $this->title, 'slug' => $this->slug, 'blocks' => $this->blocks,
                'metaDescription' => $this->metaDescription, 'isPublished' => $this->isPublished, 'updatedAt' => $this->updatedAt, 'systemKey' => $this->systemKey];
    }
}
