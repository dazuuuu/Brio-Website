<?php
declare(strict_types=1);

namespace Brio\Application\Services;

use Brio\Domain\Exceptions\NotFoundException;
use Brio\Domain\Exceptions\ValidationException;
use Brio\Domain\Link\Link;
use Brio\Domain\Page\Page;
use Brio\Domain\Page\PageRepository;
use Brio\Infrastructure\Logging\Logger;

final class PageService
{
    public function __construct(
        private readonly PageRepository $pages,
        private readonly ProductService $products,
        private readonly LinkResolver $links,
    ) {}

    /** Admin list (no block bodies). */
    public function all(): array
    {
        return array_map(static function (Page $p) {
            $a = $p->toArray();
            $a['blockCount'] = count($p->blocks);
            unset($a['blocks']);
            return $a;
        }, $this->pages->all());
    }

    public function get(int $id): array
    {
        return $this->find($id)->toArray();
    }

    /** For pickers: [{id, title, slug}] */
    public function options(): array
    {
        return array_map(static fn(Page $p) => ['id' => $p->id, 'title' => $p->title, 'slug' => $p->slug, 'isPublished' => $p->isPublished], $this->pages->all());
    }

    public function create(array $input): array
    {
        $page = Page::fromInput($input);
        $this->assertSlugFree($page->slug);
        $this->pages->save($page);
        Logger::info('Page created', ['id' => $page->id, 'slug' => $page->slug]);
        return $page->toArray();
    }

    public function update(int $id, array $input): array
    {
        $existing = $this->find($id);
        $page = Page::fromInput($input, $existing);
        $this->assertSlugFree($page->slug, $id);
        $this->pages->save($page);
        Logger::info('Page updated', ['id' => $id, 'slug' => $page->slug]);
        return $page->toArray();
    }

    public function delete(int $id): void
    {
        $page = $this->find($id);
        $this->pages->delete($id);
        Logger::info('Page deleted', ['id' => $id, 'slug' => $page->slug]);
    }

    /**
     * Storefront lookup by clean URL. Drafts are only returned when $includeDrafts (admin preview).
     * Blocks come back ready to render: links resolved, dynamic product lists loaded.
     */
    public function render(string $slug, bool $includeDrafts = false): ?array
    {
        $page = $this->pages->findBySlug($slug);
        if (!$page || (!$page->isPublished && !$includeDrafts)) return null;

        $blocks = array_map(function (array $b) {
            if (isset($b['link'])) {
                $href = $this->links->resolve(new Link($b['link']['type'] ?? 'none', $b['link']['target'] ?? null));
                $b['href'] = $href; // null → template hides the button
            }
            if ($b['type'] === 'products') $b['items'] = $this->products->forBlock($b);
            return $b;
        }, $page->blocks);

        return ['title' => $page->title, 'slug' => $page->slug, 'metaDescription' => $page->metaDescription, 'isPublished' => $page->isPublished, 'systemKey' => $page->systemKey, 'blocks' => $blocks];
    }

    private function find(int $id): Page
    {
        return $this->pages->find($id) ?? throw new NotFoundException('Page not found.');
    }

    private function assertSlugFree(string $slug, ?int $exceptId = null): void
    {
        $other = $this->pages->findBySlug($slug);
        if ($other && $other->id !== $exceptId) throw new ValidationException(['slug' => ['Another page already uses this URL.']]);
    }
}
