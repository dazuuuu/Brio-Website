<?php
declare(strict_types=1);

namespace Brio\Domain\Ad;

use Brio\Domain\Exceptions\ValidationException;
use Brio\Domain\Link\Link;

/**
 * A full-screen pop-up ad.
 *  product  shows an existing product (photo, name, offer price) and opens its page
 *  custom   an uploaded image or video and/or a message, with an optional button to any link
 */
final class Ad
{
    public const KINDS = ['product', 'custom'];
    public const FREQUENCIES = ['session', 'day', 'always'];   // once per visit / once a day / every page load
    public const PAGES = ['home', 'all'];

    public function __construct(
        public ?int $id,
        public string $name,
        public string $kind,
        public ?string $headline,
        public ?string $body,
        public ?int $productId,
        public ?string $offerText,
        public ?float $offerPrice,
        public ?string $mediaType,
        public ?string $mediaPath,
        public ?string $ctaLabel,
        public Link $link,
        public string $frequency,
        public int $delaySeconds,
        public string $pages,
        public ?string $startsAt,
        public ?string $endsAt,
        public bool $isActive,
        public int $sortOrder = 0,
        public int $views = 0,
        public int $clicks = 0,
        public ?string $createdAt = null,
    ) {}

    /** @throws ValidationException */
    public static function fromInput(array $in, ?self $existing = null): self
    {
        $e = [];
        $str = static fn(string $k, int $max, ?string $d = null): ?string => ($v = mb_substr(trim((string)($in[$k] ?? $d ?? '')), 0, $max)) !== '' ? $v : null;
        $has = static fn(string $k): bool => array_key_exists($k, $in);

        $name = trim((string)($in['name'] ?? $existing?->name ?? ''));
        if ($name === '' || mb_strlen($name) > 120) $e['name'][] = 'Give the ad a short name (only you see it).';

        $kind = (string)($in['kind'] ?? $existing?->kind ?? 'custom');
        if (!in_array($kind, self::KINDS, true)) $e['kind'][] = 'Choose a product ad or a custom ad.';

        $productId = $has('productId') ? ((int)$in['productId'] ?: null) : $existing?->productId;
        if ($kind === 'product' && $productId === null) $e['productId'][] = 'Choose the product to advertise.';

        $headline = $has('headline') ? $str('headline', 140) : $existing?->headline;
        $body = $has('body') ? $str('body', 500) : $existing?->body;
        $offerText = $has('offerText') ? $str('offerText', 60) : $existing?->offerText;
        $ctaLabel = $has('ctaLabel') ? $str('ctaLabel', 60) : $existing?->ctaLabel;

        $offerPrice = $existing?->offerPrice;
        if ($has('offerPrice')) {
            $raw = trim((string)$in['offerPrice']);
            if ($raw === '') $offerPrice = null;
            elseif (!is_numeric($raw) || (float)$raw < 0) $e['offerPrice'][] = 'Enter the discounted price as a number.';
            else $offerPrice = round((float)$raw, 2);
        }

        $link = $existing?->link ?? Link::none();
        if ($kind === 'custom' && ($has('linkType') || $has('linkTarget'))) {
            try {
                $link = Link::fromInput($in['linkType'] ?? 'none', $in['linkTarget'] ?? '', 'linkTarget');
            } catch (ValidationException $x) {
                $e += $x->errors;
            }
        }

        $frequency = (string)($in['frequency'] ?? $existing?->frequency ?? 'session');
        if (!in_array($frequency, self::FREQUENCIES, true)) $e['frequency'][] = 'Choose how often to show it.';
        $pages = (string)($in['pages'] ?? $existing?->pages ?? 'home');
        if (!in_array($pages, self::PAGES, true)) $e['pages'][] = 'Choose where to show it.';
        $delay = (int)($in['delaySeconds'] ?? $existing?->delaySeconds ?? 2);
        if ($delay < 0 || $delay > 30) $e['delaySeconds'][] = 'Use 0 to 30 seconds.';

        $starts = $has('startsAt') ? self::parseDate((string)$in['startsAt'], 'startsAt', $e) : $existing?->startsAt;
        $ends = $has('endsAt') ? self::parseDate((string)$in['endsAt'], 'endsAt', $e) : $existing?->endsAt;
        if ($starts && $ends && strtotime($ends) <= strtotime($starts)) $e['endsAt'][] = 'The end must be after the start.';

        if ($e) throw new ValidationException($e);

        // Link and button only apply to custom ads; product ads always open the product.
        if ($kind === 'product') {
            $link = Link::none();
        } elseif ($link->isNone()) {
            $ctaLabel = null;
        } elseif ($ctaLabel === null) {
            $ctaLabel = 'Learn more';
        }

        return new self($existing?->id, $name, $kind, $headline, $body, $kind === 'product' ? $productId : null, $offerText, $kind === 'product' ? $offerPrice : null,
            $existing?->mediaType, $existing?->mediaPath, $ctaLabel, $link, $frequency, $delay, $pages, $starts, $ends,
            $has('isActive') ? filter_var($in['isActive'], FILTER_VALIDATE_BOOLEAN) : ($existing?->isActive ?? true),
            $existing?->sortOrder ?? 0, $existing?->views ?? 0, $existing?->clicks ?? 0, $existing?->createdAt);
    }

    /** Accepts "2026-12-01T09:30" (browser) or "2026-12-01 09:30:00" (database). Empty = no limit. */
    private static function parseDate(string $raw, string $field, array &$errors): ?string
    {
        $raw = trim($raw);
        if ($raw === '') return null;
        foreach (['Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i:s'] as $format) {
            $d = \DateTimeImmutable::createFromFormat($format, $raw);
            if ($d !== false) return $d->format('Y-m-d H:i:s');
        }
        $errors[$field][] = 'Enter a valid date and time.';
        return null;
    }

    public function status(\DateTimeImmutable $now): string
    {
        if (!$this->isActive) return 'paused';
        if ($this->startsAt && $now < new \DateTimeImmutable($this->startsAt)) return 'scheduled';
        if ($this->endsAt && $now >= new \DateTimeImmutable($this->endsAt)) return 'ended';
        return 'live';
    }

    public function toArray(?\DateTimeImmutable $now = null): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'kind' => $this->kind, 'headline' => $this->headline, 'body' => $this->body,
            'productId' => $this->productId, 'offerText' => $this->offerText, 'offerPrice' => $this->offerPrice,
            'mediaType' => $this->mediaType, 'mediaPath' => $this->mediaPath, 'ctaLabel' => $this->ctaLabel,
            'linkType' => $this->link->type, 'linkTarget' => $this->link->target,
            'frequency' => $this->frequency, 'delaySeconds' => $this->delaySeconds, 'pages' => $this->pages,
            'startsAt' => $this->startsAt ? str_replace(' ', 'T', substr($this->startsAt, 0, 16)) : null,
            'endsAt' => $this->endsAt ? str_replace(' ', 'T', substr($this->endsAt, 0, 16)) : null,
            'isActive' => $this->isActive, 'sortOrder' => $this->sortOrder, 'views' => $this->views, 'clicks' => $this->clicks,
            'status' => $this->status($now ?? new \DateTimeImmutable()),
        ];
    }
}
