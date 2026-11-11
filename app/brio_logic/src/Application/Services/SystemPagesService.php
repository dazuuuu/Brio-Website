<?php
declare(strict_types=1);

namespace Brio\Application\Services;

use Brio\Domain\Exceptions\NotFoundException;
use Brio\Domain\Exceptions\ValidationException;
use Brio\Domain\Page\Blocks;
use Brio\Domain\Page\Page;
use Brio\Domain\Page\PageRepository;
use Brio\Domain\Page\SystemPages;
use Brio\Domain\Settings\SettingsRepository;
use Brio\Infrastructure\Logging\Logger;

/** Creates the built-in pages once, and lets the admin restore ones they deleted. */
final class SystemPagesService
{
    private const FLAG = 'system_pages_seeded';

    public function __construct(private readonly PageRepository $pages, private readonly SettingsRepository $settings, private readonly ?string $marker = null) {}

    /** First run only: add the built-in pages. Later deletions are respected (never re-added automatically). */
    public function seedOnce(): void
    {
        if ($this->marker !== null && is_file($this->marker)) return;
        if (!isset($this->settings->all()[self::FLAG])) {
            foreach (array_keys(SystemPages::defaults()) as $key) {
                if ($this->pages->findBySystemKey($key) === null) $this->create($key);
            }
            $this->settings->set([self::FLAG => '1']);
            Logger::info('Built-in pages created');
        }
        if ($this->marker !== null) @file_put_contents($this->marker, '1', LOCK_EX);
    }

    /** Built-in pages the admin has deleted. @return array<int,array{key:string,title:string,description:string,path:string}> */
    public function removed(): array
    {
        $out = [];
        foreach (SystemPages::defaults() as $key => $def) {
            if ($this->pages->findBySystemKey($key) === null) {
                $out[] = ['key' => $key, 'title' => $def['title'], 'description' => $def['description'], 'path' => '/' . $def['slug']];
            }
        }
        return $out;
    }

    public function restore(mixed $key): array
    {
        $defaults = SystemPages::defaults();
        if (!is_string($key) || !isset($defaults[$key])) throw new NotFoundException('Unknown built-in page.');
        if ($this->pages->findBySystemKey($key) !== null) throw new ValidationException(['key' => ['That page already exists.']]);
        $page = $this->create($key);
        Logger::info('Built-in page restored', ['key' => $key]);
        return $page->toArray();
    }

    private function create(string $key): Page
    {
        $def = SystemPages::defaults()[$key];
        $page = new Page(null, $def['title'], $def['slug'], Blocks::sanitize($def['blocks']), null, true, null, $key);
        return $this->pages->saveSystem($page);
    }
}
