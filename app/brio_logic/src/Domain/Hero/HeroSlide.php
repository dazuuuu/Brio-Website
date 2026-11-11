<?php
declare(strict_types=1);

namespace Brio\Domain\Hero;

use Brio\Domain\Exceptions\ValidationException;
use Brio\Domain\Link\Link;

/** A full-image hero slide. The CTA button is optional and only exists when a link is chosen. */
final class HeroSlide
{
    public const DEFAULT_COLOR = '#1b2e24';

    public function __construct(
        public ?int $id,
        public string $image,
        public ?string $mobileImage,
        public string $alt,
        public ?string $ctaLabel,
        public Link $link,
        public string $ctaColor,
        public int $sortOrder,
        public bool $isActive,
    ) {}

    public static function fromInput(array $in, ?self $existing = null): self
    {
        $e = [];
        $alt = trim((string)($in['alt'] ?? $existing?->alt ?? ''));
        if (mb_strlen($alt) > 160) $e['alt'][] = 'Keep the description under 160 characters.';

        $color = strtolower(trim((string)($in['ctaColor'] ?? $existing?->ctaColor ?? self::DEFAULT_COLOR)));
        if (!preg_match('/^#[0-9a-f]{6}$/', $color)) $e['ctaColor'][] = 'Choose a valid colour.';

        $label = array_key_exists('ctaLabel', $in) ? trim((string)$in['ctaLabel']) : (string)($existing?->ctaLabel ?? '');
        if (mb_strlen($label) > 60) $e['ctaLabel'][] = 'Keep the button text under 60 characters.';

        try {
            $link = Link::fromInput($in['linkType'] ?? $existing?->link->type ?? 'none', $in['linkTarget'] ?? $existing?->link->target ?? '', 'linkTarget');
        } catch (ValidationException $x) {
            $e += $x->errors;
            $link = Link::none();
        }
        if ($e) throw new ValidationException($e);

        // No link → no button. A link with no label gets a sensible default.
        $label = $link->isNone() ? '' : ($label !== '' ? $label : 'Shop now');
        $active = isset($in['isActive']) ? filter_var($in['isActive'], FILTER_VALIDATE_BOOLEAN) : ($existing?->isActive ?? true);

        return new self($existing?->id, $existing?->image ?? '', $existing?->mobileImage, $alt, $label !== '' ? $label : null, $link, $color, $existing?->sortOrder ?? 0, $active);
    }

    /** Readable text colour (dark or white) for a button of the given background colour. */
    public static function textColorFor(string $hex): string
    {
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));
        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 150 ? '#1b2e24' : '#ffffff';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'image' => $this->image, 'mobileImage' => $this->mobileImage, 'alt' => $this->alt,
            'ctaLabel' => $this->ctaLabel, 'linkType' => $this->link->type, 'linkTarget' => $this->link->target,
            'ctaColor' => $this->ctaColor, 'sortOrder' => $this->sortOrder, 'isActive' => $this->isActive,
        ];
    }
}
