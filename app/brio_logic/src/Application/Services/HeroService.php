<?php
declare(strict_types=1);

namespace Brio\Application\Services;

use Brio\Domain\Exceptions\NotFoundException;
use Brio\Domain\Exceptions\ValidationException;
use Brio\Domain\Hero\HeroSlide;
use Brio\Domain\Hero\HeroSlideRepository;
use Brio\Infrastructure\Files\FileHandler;
use Brio\Infrastructure\Logging\Logger;

final class HeroService
{
    public function __construct(
        private readonly HeroSlideRepository $slides,
        private readonly FileHandler $files,
        private readonly LinkResolver $links,
    ) {}

    /** Admin list. */
    public function all(): array
    {
        return array_map(fn(HeroSlide $s) => $s->toArray(), $this->slides->all());
    }

    /** Storefront: active slides with the button's final href (button dropped if its target is gone). */
    public function forStorefront(): array
    {
        $out = [];
        foreach ($this->slides->all(true) as $s) {
            $href = $s->ctaLabel !== null ? $this->links->resolve($s->link) : null;
            $out[] = ['image' => $s->image, 'mobileImage' => $s->mobileImage, 'alt' => $s->alt,
                      'ctaLabel' => $href !== null ? $s->ctaLabel : null, 'href' => $href,
                      'ctaColor' => $s->ctaColor, 'ctaTextColor' => HeroSlide::textColorFor($s->ctaColor)];
        }
        return $out;
    }

    public function create(array $input, ?array $image, ?array $mobileImage): array
    {
        if (!$this->hasFile($image)) throw new ValidationException(['image' => ['Upload the slide image.']]);
        $slide = HeroSlide::fromInput($input);
        $slide->image = $this->files->storeImage($image, 'hero');
        if ($this->hasFile($mobileImage)) $slide->mobileImage = $this->files->storeImage($mobileImage, 'hero');
        $this->slides->save($slide);
        Logger::info('Hero slide created', ['id' => $slide->id]);
        return $slide->toArray();
    }

    public function update(int $id, array $input, ?array $image, ?array $mobileImage): array
    {
        $existing = $this->find($id);
        $slide = HeroSlide::fromInput($input, $existing);
        if ($this->hasFile($image)) {
            $slide->image = $this->files->storeImage($image, 'hero');
            $this->files->delete($existing->image);
        }
        if ($this->hasFile($mobileImage)) {
            $slide->mobileImage = $this->files->storeImage($mobileImage, 'hero');
            if ($existing->mobileImage) $this->files->delete($existing->mobileImage);
        } elseif (!empty($input['removeMobile']) && $existing->mobileImage) {
            $this->files->delete($existing->mobileImage);
            $slide->mobileImage = null;
        }
        $this->slides->save($slide);
        Logger::info('Hero slide updated', ['id' => $id]);
        return $slide->toArray();
    }

    public function delete(int $id): void
    {
        $slide = $this->find($id);
        $this->slides->delete($id);
        $this->files->delete($slide->image);
        if ($slide->mobileImage) $this->files->delete($slide->mobileImage);
        Logger::info('Hero slide deleted', ['id' => $id]);
    }

    /** @param mixed $ids */
    public function reorder(mixed $ids): void
    {
        if (!is_array($ids)) throw new ValidationException(['ids' => ['Send the slide ids in order.']]);
        $this->slides->reorder(array_map('intval', $ids));
    }

    private function find(int $id): HeroSlide
    {
        return $this->slides->find($id) ?? throw new NotFoundException('Slide not found.');
    }

    private function hasFile(?array $f): bool
    {
        return $f !== null && ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }
}
