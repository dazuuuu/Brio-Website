<?php
declare(strict_types=1);

namespace Brio\Application\Services;

use Brio\Domain\Exceptions\NotFoundException;
use Brio\Domain\Exceptions\ValidationException;
use Brio\Infrastructure\Files\FileHandler;
use Brio\Infrastructure\Files\IconGenerator;
use Brio\Infrastructure\Logging\Logger;

/** Logos, site icon and the share picture, changed from the admin. Files live in /uploads/brand. */
final class BrandService
{
    /** URL slot name => settings key */
    private const IMAGES = ['logo' => 'logo', 'logo-dark' => 'logo_dark', 'share-image' => 'share_image_path'];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly FileHandler $files,
        private readonly IconGenerator $icons,
        private readonly string $publicRoot,
    ) {}

    /** Upload a logo / dark logo / share image. @param array<string,mixed>|null $file one $_FILES entry */
    public function setImage(string $slot, ?array $file): array
    {
        $key = self::IMAGES[$slot] ?? throw new NotFoundException('Unknown image slot.');
        $this->requireFile($file);
        $old = $this->settings->get()[$key];

        $path = $this->files->storeImage($file, 'brand');
        $values = [$key => $path];
        if ($slot === 'share-image') $values['share_image'] = 'custom';
        $this->settings->setInternal($values);
        if ($old !== '') $this->files->delete($old);
        if ($slot === 'logo') $this->makeShareCard($path);
        Logger::info('Brand image changed', ['slot' => $slot]);
        return $this->settings->forAdmin();
    }

    public function clearImage(string $slot): array
    {
        $key = self::IMAGES[$slot] ?? throw new NotFoundException('Unknown image slot.');
        $old = $this->settings->get()[$key];
        $values = [$key => ''];
        if ($slot === 'share-image') $values['share_image'] = 'popular';
        $this->settings->setInternal($values);
        if ($old !== '') $this->files->delete($old);
        if ($slot === 'logo') $this->files->delete('uploads/brand/og-logo.png');
        return $this->settings->forAdmin();
    }

    /** Upload an image and turn it into favicon.ico, Apple touch icon and the app icons. */
    public function setIcon(?array $file): array
    {
        $this->requireFile($file);
        $stored = $this->files->storeImage($file, 'brand');
        try {
            $this->icons->generate($this->publicRoot . '/' . $stored, $this->publicRoot . '/uploads/brand/icons');
        } finally {
            $this->files->delete($stored); // only the generated icons are kept
        }
        $this->settings->setInternal(['site_icon' => (string)time()]);
        Logger::info('Site icon changed');
        return $this->settings->forAdmin();
    }

    public function clearIcon(): array
    {
        foreach (IconGenerator::FILES as $name) $this->files->delete('uploads/brand/icons/' . $name);
        $this->settings->setInternal(['site_icon' => '']);
        return $this->settings->forAdmin();
    }

    /** A 1200x630 logo-on-white picture for link previews (best effort). */
    private function makeShareCard(string $logoPath): void
    {
        $this->icons->shareCard($this->publicRoot . '/' . $logoPath, $this->publicRoot . '/uploads/brand/og-logo.png');
    }

    private function requireFile(?array $file): void
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new ValidationException(['image' => ['Choose an image to upload.']]);
        }
    }
}
