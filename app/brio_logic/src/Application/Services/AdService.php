<?php
declare(strict_types=1);

namespace Brio\Application\Services;

use Brio\Domain\Ad\Ad;
use Brio\Domain\Ad\AdRepository;
use Brio\Domain\Exceptions\NotFoundException;
use Brio\Domain\Exceptions\ValidationException;
use Brio\Domain\Link\Link;
use Brio\Domain\Product\ProductRepository;
use Brio\Infrastructure\Files\FileHandler;
use Brio\Infrastructure\Logging\Logger;

/** Pop-up ads: managed in the admin, shown to shoppers. */
final class AdService
{
    /** Pages where an ad must never interrupt the shopper. */
    private const NEVER_ON = ['checkout', 'account', 'login', 'register', '404'];
    private const MAX_PER_PAGE = 5;

    public function __construct(
        private readonly AdRepository $ads,
        private readonly ProductRepository $products,
        private readonly LinkResolver $links,
        private readonly FileHandler $files,
    ) {}

    /** Admin list, with the product behind each product ad. */
    public function all(): array
    {
        $now = new \DateTimeImmutable();
        $ids = array_filter(array_map(static fn(Ad $a) => $a->productId, $this->ads->all()));
        $products = $this->products->findMany($ids);
        return array_map(function (Ad $a) use ($now, $products) {
            $row = $a->toArray($now);
            $p = $a->productId !== null ? ($products[$a->productId] ?? null) : null;
            $row['productName'] = $p?->name;
            $row['productImage'] = $p?->image;
            return $row;
        }, $this->ads->all());
    }

    public function create(array $input, ?array $media): array
    {
        $ad = Ad::fromInput($input);
        $this->applyMedia($ad, $media, false, null);
        $this->assertHasContent($ad);
        $this->ads->save($ad);
        Logger::info('Ad created', ['id' => $ad->id, 'kind' => $ad->kind]);
        return $ad->toArray();
    }

    public function update(int $id, array $input, ?array $media): array
    {
        $existing = $this->find($id);
        $ad = Ad::fromInput($input, $existing);
        $this->applyMedia($ad, $media, !empty($input['removeMedia']), $existing);
        $this->assertHasContent($ad);
        $this->ads->save($ad);
        Logger::info('Ad updated', ['id' => $id]);
        return $ad->toArray();
    }

    public function delete(int $id): void
    {
        $ad = $this->find($id);
        $this->ads->delete($id);
        if ($ad->mediaPath) $this->files->delete($ad->mediaPath);
        Logger::info('Ad deleted', ['id' => $id]);
    }

    public function reorder(mixed $ids): void
    {
        if (!is_array($ids)) throw new ValidationException(['ids' => ['Send the ad ids in order.']]);
        $this->ads->reorder(array_map('intval', $ids));
    }

    /** Ads to embed in a storefront page (already resolved to links, prices and photos). */
    public function forStorefront(string $route): array
    {
        if (in_array($route, self::NEVER_ON, true)) return [];
        $now = new \DateTimeImmutable();
        $out = [];
        foreach ($this->ads->all() as $ad) {
            if ($ad->status($now) !== 'live') continue;
            if ($ad->pages === 'home' && $route !== '') continue;
            if (($resolved = $this->resolve($ad)) !== null) $out[] = $resolved;
            if (count($out) >= self::MAX_PER_PAGE) break;
        }
        return $out;
    }

    /** For the admin's "preview on the store": shows one ad now, ignoring schedule and frequency. */
    public function preview(int $id): ?array
    {
        $ad = $this->ads->find($id);
        $resolved = $ad ? $this->resolve($ad) : null;
        return $resolved ? $resolved + ['force' => true, 'delay' => 0] : null;
    }

    /** A shopper saw or clicked an ad. */
    public function record(int $id, mixed $type): void
    {
        if (!in_array($type, ['view', 'click'], true) || $this->ads->find($id) === null) return;
        $this->ads->increment($id, $type === 'click' ? 'clicks' : 'views');
    }

    /** @return array<string,mixed>|null null when the ad can't be shown (product gone or out of stock, link broken) */
    private function resolve(Ad $ad): ?array
    {
        $base = ['id' => $ad->id, 'kind' => $ad->kind, 'headline' => $ad->headline, 'body' => $ad->body, 'frequency' => $ad->frequency, 'delay' => $ad->delaySeconds,
                 'offerText' => $ad->offerText, 'force' => false];

        if ($ad->kind === 'product') {
            $p = $ad->productId !== null ? $this->products->find($ad->productId) : null;
            if ($p === null || $p->stock <= 0) return null;
            $price = $ad->offerPrice ?? $p->price;
            $was = $ad->offerPrice !== null ? $p->price : $p->compareAtPrice;
            $href = 'products/' . $p->slug;
            return $base + ['media' => null, 'product' => ['name' => $p->name, 'image' => $p->image, 'price' => $price, 'compareAt' => ($was !== null && $was > $price) ? $was : null],
                            'cta' => ['label' => $ad->ctaLabel ?: 'Shop now', 'href' => $href]];
        }

        $cta = null;
        if (!$ad->link->isNone()) {
            $href = $this->links->resolve($ad->link);
            if ($href !== null) $cta = ['label' => $ad->ctaLabel ?: 'Learn more', 'href' => $href];
        }
        return $base + ['product' => null, 'media' => $ad->mediaPath ? ['type' => $ad->mediaType, 'src' => $ad->mediaPath] : null, 'cta' => $cta];
    }

    /** Store a new upload, or remove the current media, and clean up the replaced file. */
    private function applyMedia(Ad $ad, ?array $upload, bool $remove, ?Ad $existing): void
    {
        $uploaded = $upload !== null && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($uploaded) {
            $stored = $this->files->storeMedia($upload, 'ads');
            $ad->mediaType = $stored['type'];
            $ad->mediaPath = $stored['path'];
            if ($existing?->mediaPath) $this->files->delete($existing->mediaPath);
        } elseif ($remove && $existing?->mediaPath) {
            $this->files->delete($existing->mediaPath);
            $ad->mediaType = $ad->mediaPath = null;
        }
        if ($ad->kind === 'product' && $ad->mediaPath) { // product ads show the product, not uploaded media
            $this->files->delete($ad->mediaPath);
            $ad->mediaType = $ad->mediaPath = null;
        }
    }

    private function assertHasContent(Ad $ad): void
    {
        if ($ad->kind === 'custom' && !$ad->headline && !$ad->body && !$ad->mediaPath) {
            throw new ValidationException(['headline' => ['Add a picture, a video or some text so there is something to show.']]);
        }
    }

    private function find(int $id): Ad
    {
        return $this->ads->find($id) ?? throw new NotFoundException('Ad not found.');
    }
}
