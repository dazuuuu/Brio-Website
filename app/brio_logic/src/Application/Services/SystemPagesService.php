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

    private const KEYS_SETTING = 'system_pages_keys';
    /** The six pages created before per-page tracking existed. */
    private const LEGACY_KEYS = ['home', 'shop', 'checkout', 'login', 'register', 'account'];

    /**
     * Creates each built-in page once. A page the admin deletes is never re-added by itself, and pages added in later
     * versions (like About us) are created on existing stores the first time they load.
     */
    public function seedOnce(): void
    {
        $signature = md5(implode(',', array_keys(SystemPages::defaults())));
        if ($this->marker !== null && is_file($this->marker) && trim((string)file_get_contents($this->marker)) === $signature) return;

        $stored = $this->settings->all();
        $done = json_decode($stored[self::KEYS_SETTING] ?? '[]', true);
        $done = is_array($done) ? $done : [];
        if (!$done && isset($stored[self::FLAG])) $done = self::LEGACY_KEYS;

        $created = [];
        foreach (SystemPages::defaults() as $key => $def) {
            if (in_array($key, $done, true)) continue;
            // Skip (but remember) when the owner already has a page with that address.
            if ($this->pages->findBySystemKey($key) === null && $this->pages->findBySlug($def['slug']) === null) {
                $this->create($key);
                $created[] = $key;
            }
            $done[] = $key;
        }
        $this->settings->set([self::FLAG => '1', self::KEYS_SETTING => json_encode(array_values(array_unique($done)))]);
        if ($created) Logger::info('Built-in pages created', ['pages' => $created]);
        if ($this->marker !== null) @file_put_contents($this->marker, $signature, LOCK_EX);
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
