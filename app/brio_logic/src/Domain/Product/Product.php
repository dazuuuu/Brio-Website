<?php
declare(strict_types=1);

namespace Brio\Domain\Product;

use Brio\Domain\Exceptions\ValidationException;

/** Product entity: the only place product business rules live. */
final class Product
{
    public const CATEGORIES = ['skin-care' => 'Skin Care', 'bath-body' => 'Bath & Body'];
    public const DEAL_TONES = ['mint', 'rose', 'sage', 'gold'];

    public function __construct(
        public ?int $id,
        public string $name,
        public string $slug,
        public string $description,
        public string $category,
        public float $price,
        public ?float $compareAtPrice,
        public string $image,
        public float $rating,
        public int $reviewCount,
        public int $stock,
        public int $sales,
        public bool $isFeatured,
        public bool $isDeal,
        public ?string $badge,
        public ?string $dealEyebrow,
        public ?string $dealTag,
        public string $dealTone,
    ) {}

    /** Build from untrusted input (admin form / API); throws ValidationException. */
    public static function fromInput(array $in, ?self $existing = null): self
    {
        $e = [];
        $str = static fn(string $k, string $d = ''): string => trim((string)($in[$k] ?? $d));
        $num = static fn(string $k, $d) => isset($in[$k]) && $in[$k] !== '' ? $in[$k] : $d;
        $bool = static fn(string $k, bool $d): bool => isset($in[$k]) ? filter_var($in[$k], FILTER_VALIDATE_BOOLEAN) : $d;

        $name = $str('name', $existing?->name ?? '');
        if ($name === '' || mb_strlen($name) > 160) $e['name'][] = 'Name is required (max 160 characters).';

        $category = $str('category', $existing?->category ?? '');
        if (!isset(self::CATEGORIES[$category])) $e['category'][] = 'Choose a valid category.';

        $price = $num('price', $existing?->price);
        if (!is_numeric($price) || $price < 0) $e['price'][] = 'Price must be a positive number.';

        $compare = array_key_exists('compareAtPrice', $in) ? ($in['compareAtPrice'] === '' || $in['compareAtPrice'] === null ? null : $in['compareAtPrice']) : $existing?->compareAtPrice;
        if ($compare !== null && (!is_numeric($compare) || $compare < 0)) $e['compareAtPrice'][] = 'Compare-at price must be a positive number.';

        $stock = $num('stock', $existing?->stock ?? 0);
        if (filter_var($stock, FILTER_VALIDATE_INT) === false || $stock < 0) $e['stock'][] = 'Stock must be a whole number ≥ 0.';

        $sales = $num('sales', $existing?->sales ?? 0);
        if (filter_var($sales, FILTER_VALIDATE_INT) === false || $sales < 0) $e['sales'][] = 'Sales must be a whole number ≥ 0.';

        $rating = $num('rating', $existing?->rating ?? 0);
        if (!is_numeric($rating) || $rating < 0 || $rating > 5) $e['rating'][] = 'Rating must be between 0 and 5.';

        $reviews = $num('reviewCount', $existing?->reviewCount ?? 0);
        if (filter_var($reviews, FILTER_VALIDATE_INT) === false || $reviews < 0) $e['reviewCount'][] = 'Review count must be a whole number ≥ 0.';

        $tone = $str('dealTone', $existing?->dealTone ?? 'mint');
        if (!in_array($tone, self::DEAL_TONES, true)) $e['dealTone'][] = 'Choose a valid card colour.';

        $nullable = static function (string $k, ?string $d) use ($in): ?string {
            if (!array_key_exists($k, $in)) return $d;
            $v = trim((string)$in[$k]);
            return $v === '' ? null : mb_substr($v, 0, 60);
        };

        if ($e) throw new ValidationException($e);

        return new self(
            $existing?->id,
            $name,
            self::slugify($name),
            $str('description', $existing?->description ?? ''),
            $category,
            round((float)$price, 2),
            $compare === null ? null : round((float)$compare, 2),
            $existing?->image ?? '',
            round((float)$rating, 1),
            (int)$reviews,
            (int)$stock,
            (int)$sales,
            $bool('isFeatured', $existing?->isFeatured ?? false),
            $bool('isDeal', $existing?->isDeal ?? false),
            $nullable('badge', $existing?->badge),
            $nullable('dealEyebrow', $existing?->dealEyebrow),
            $nullable('dealTag', $existing?->dealTag),
            $tone,
        );
    }

    public static function slugify(string $s): string
    {
        $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s) ?? '', '-'));
        return $s !== '' ? $s : 'product';
    }

    public function revenue(): float
    {
        return $this->price * $this->sales;
    }

    public function stockStatus(int $lowThreshold): string
    {
        return $this->stock <= 0 ? 'out' : ($this->stock <= $lowThreshold ? 'low' : 'in');
    }

    /** Shape used by the storefront templates and the JSON API. */
    public function toArray(int $lowThreshold = 10): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'slug' => $this->slug, 'description' => $this->description,
            'category' => $this->category, 'categoryLabel' => self::CATEGORIES[$this->category] ?? $this->category,
            'price' => $this->price, 'compareAtPrice' => $this->compareAtPrice, 'image' => $this->image,
            'rating' => $this->rating, 'reviewCount' => $this->reviewCount, 'stock' => $this->stock,
            'sales' => $this->sales, 'revenue' => $this->revenue(), 'stockStatus' => $this->stockStatus($lowThreshold),
            'isFeatured' => $this->isFeatured, 'isDeal' => $this->isDeal, 'badge' => $this->badge,
            'dealEyebrow' => $this->dealEyebrow, 'dealTag' => $this->dealTag, 'dealTone' => $this->dealTone,
        ];
    }
}
