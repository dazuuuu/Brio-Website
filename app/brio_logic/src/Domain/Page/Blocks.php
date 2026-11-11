<?php
declare(strict_types=1);

namespace Brio\Domain\Page;

use Brio\Domain\Exceptions\ValidationException;
use Brio\Domain\Link\Link;
use Brio\Domain\Product\Product;

/**
 * Whitelist sanitiser for page-builder blocks. Anything not listed here is dropped or rejected,
 * so stored page content can never carry raw HTML or scripts.
 */
final class Blocks
{
    public const TYPES = ['heading', 'text', 'image', 'image_text', 'button', 'products', 'categories', 'divider', 'section'];
    /** Built-in parts of the store that can be placed on a page (once each). */
    public const SECTIONS = ['hero', 'deals', 'catalog', 'login', 'register', 'account', 'checkout'];
    public const PRODUCT_SOURCES = ['featured', 'deals', 'latest', 'category', 'selected'];
    private const MAX_BLOCKS = 100;

    public static function sanitize(mixed $blocks): array
    {
        if (!is_array($blocks)) throw new ValidationException(['blocks' => ['Page content must be a list of blocks.']]);
        if (count($blocks) > self::MAX_BLOCKS) throw new ValidationException(['blocks' => ['A page can have up to ' . self::MAX_BLOCKS . ' blocks.']]);

        $clean = [];
        $errors = [];
        foreach (array_values($blocks) as $i => $block) {
            $n = $i + 1;
            $type = is_array($block) ? (string)($block['type'] ?? '') : '';
            if (!in_array($type, self::TYPES, true)) { $errors['blocks'][] = "Block $n has an unknown type."; continue; }
            try {
                $clean[] = self::one($type, $block);
            } catch (ValidationException $x) {
                foreach ($x->errors as $messages) foreach ($messages as $m) $errors['blocks'][] = "Block $n (" . str_replace('_', ' ', $type) . '): ' . $m;
            }
        }
        // Each built-in section may appear once per page (their forms and scripts are single-instance).
        $names = array_column(array_filter($clean, static fn($b) => $b['type'] === 'section'), 'name');
        foreach (array_keys(array_filter(array_count_values($names), static fn($n) => $n > 1)) as $dupe) {
            $errors['blocks'][] = "The “{$dupe}” section can only be on a page once.";
        }
        if ($errors) throw new ValidationException($errors, $errors['blocks'][0]);
        return $clean;
    }

    private static function one(string $type, array $b): array
    {
        $id = preg_match('/^[a-z0-9]{4,16}$/i', (string)($b['id'] ?? '')) ? (string)$b['id'] : bin2hex(random_bytes(4));
        $str = static fn(string $k, int $max): string => mb_substr(trim((string)($b[$k] ?? '')), 0, $max);
        $enum = static fn(string $k, array $allowed, string $default): string => in_array($b[$k] ?? null, $allowed, true) ? $b[$k] : $default;
        $need = static function (string $value, string $message): void {
            if ($value === '') throw new ValidationException(['b' => [$message]]);
        };
        $align = $enum('align', ['left', 'center', 'right'], 'left');
        $out = ['id' => $id, 'type' => $type];

        switch ($type) {
            case 'heading':
                $need($out['text'] = $str('text', 200), 'Enter the heading text.');
                return $out + ['level' => $enum('level', ['h2', 'h3'], 'h2'), 'align' => $align];

            case 'text':
                $need($out['text'] = $str('text', 10000), 'Enter some text.');
                return $out + ['align' => $align];

            case 'image':
                $out['src'] = self::imagePath($str('src', 255));
                return $out + ['alt' => $str('alt', 160), 'caption' => $str('caption', 200), 'width' => $enum('width', ['narrow', 'wide', 'full'], 'wide')];

            case 'image_text':
                $out['src'] = self::imagePath($str('src', 255));
                $link = is_array($b['link'] ?? null) ? $b['link'] : [];
                $l = Link::fromInput($link['type'] ?? 'none', $link['target'] ?? '', 'link');
                return $out + ['alt' => $str('alt', 160), 'title' => $str('title', 200), 'text' => $str('text', 4000),
                    'side' => $enum('side', ['left', 'right'], 'left'), 'ctaLabel' => $l->isNone() ? '' : ($str('ctaLabel', 60) ?: 'Learn more'), 'link' => $l->toArray()];

            case 'button':
                $need($out['label'] = $str('label', 60), 'Enter the button text.');
                $link = is_array($b['link'] ?? null) ? $b['link'] : [];
                $l = Link::fromInput($link['type'] ?? 'none', $link['target'] ?? '', 'link');
                if ($l->isNone()) throw new ValidationException(['link' => ['Choose where the button links to.']]);
                return $out + ['link' => $l->toArray(), 'align' => $align, 'style' => $enum('style', ['primary', 'outline'], 'primary')];

            case 'products':
                $source = $enum('source', self::PRODUCT_SOURCES, 'featured');
                $category = (string)($b['category'] ?? '');
                if ($source === 'category' && !isset(Product::CATEGORIES[$category])) throw new ValidationException(['category' => ['Choose a category.']]);
                $ids = array_values(array_unique(array_filter(array_map('intval', is_array($b['ids'] ?? null) ? $b['ids'] : []), static fn($v) => $v > 0)));
                if ($source === 'selected' && !$ids) throw new ValidationException(['ids' => ['Pick at least one product.']]);
                return $out + ['title' => $str('title', 100), 'subtitle' => $str('subtitle', 200), 'source' => $source,
                    'category' => $source === 'category' ? $category : '', 'ids' => $source === 'selected' ? array_slice($ids, 0, 24) : [],
                    'limit' => max(1, min(24, (int)($b['limit'] ?? 8)))];

            case 'section':
                $name = (string)($b['name'] ?? '');
                if (!in_array($name, self::SECTIONS, true)) throw new ValidationException(['name' => ['Unknown store section.']]);
                $out['name'] = $name;
                return $name === 'deals' ? $out + ['title' => $str('title', 100), 'subtitle' => $str('subtitle', 200)] : $out;

            case 'categories':
                return $out + ['title' => $str('title', 100)];

            default: // divider
                return $out;
        }
    }

    /** Only files we host: uploads/… or assets/… (no external URLs, no path tricks). */
    private static function imagePath(string $path): string
    {
        if ($path === '') throw new ValidationException(['src' => ['Upload an image.']]);
        if (!preg_match('#^(uploads|assets)/[A-Za-z0-9_./-]+$#', $path) || str_contains($path, '..')) {
            throw new ValidationException(['src' => ['That image path is not valid. Upload the image again.']]);
        }
        return $path;
    }
}
