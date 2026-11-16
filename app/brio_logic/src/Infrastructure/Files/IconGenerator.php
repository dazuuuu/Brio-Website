<?php
declare(strict_types=1);

namespace Brio\Infrastructure\Files;

use Brio\Domain\Exceptions\ValidationException;

/**
 * Turns one image into the full set of site icons browsers and phones ask for:
 * favicon.ico (16/32/48), favicon-32.png, apple-touch-icon.png (180), icon-192.png, icon-512.png.
 * Needs PHP's GD extension (standard on virtually every host).
 */
final class IconGenerator
{
    public const FILES = ['favicon.ico', 'favicon-32.png', 'apple-touch-icon.png', 'icon-192.png', 'icon-512.png'];
    private const MAX_PIXELS = 25_000_000; // refuse absurdly large images instead of exhausting memory

    public static function available(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatefromstring');
    }

    /**
     * @param string $sourceFile any PNG/JPG/WebP (square or not: it is fitted inside a square with a little padding)
     * @param string $dir        folder to write the icon files into (created if needed)
     * @return string[] the file names written
     */
    public function generate(string $sourceFile, string $dir): array
    {
        if (!self::available()) {
            throw new ValidationException(['image' => ['This server cannot process images (the PHP “GD” extension is off). Ask your host to enable it.']]);
        }
        $info = @getimagesize($sourceFile);
        if ($info === false || $info[0] * $info[1] > self::MAX_PIXELS) {
            throw new ValidationException(['image' => ['Use a PNG, JPG or WebP image under about 5000 × 5000 pixels.']]);
        }
        $source = @imagecreatefromstring((string)file_get_contents($sourceFile));
        if ($source === false) throw new ValidationException(['image' => ['That image could not be read.']]);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new \RuntimeException('Icon folder is not writable.');

        $png = static function (\GdImage $img): string { ob_start(); imagepng($img, null, 9); return (string)ob_get_clean(); };
        $transparent = fn(int $size) => $this->fit($source, $size, null);

        $ico = [];
        foreach ([16, 32, 48] as $size) $ico[$size] = $png($transparent($size));
        $head = pack('vvv', 0, 1, count($ico));
        $entries = $data = '';
        $offset = 6 + 16 * count($ico);
        foreach ($ico as $size => $bytes) {
            $entries .= pack('CCCCvvVV', $size, $size, 0, 0, 1, 32, strlen($bytes), $offset + strlen($data));
            $data .= $bytes;
        }
        $this->write("$dir/favicon.ico", $head . $entries . $data);
        $this->write("$dir/favicon-32.png", $ico[32]);
        $this->write("$dir/apple-touch-icon.png", $png($this->fit($source, 180, [255, 255, 255]))); // iOS fills transparency with black
        $this->write("$dir/icon-192.png", $png($transparent(192)));
        $this->write("$dir/icon-512.png", $png($transparent(512)));
        return self::FILES;
    }

    /** A 1200x630 "link preview" picture: the logo centred on white. Returns false if GD can't do it. */
    public function shareCard(string $sourceFile, string $destFile): bool
    {
        if (!self::available()) return false;
        $info = @getimagesize($sourceFile);
        if ($info === false || $info[0] * $info[1] > self::MAX_PIXELS) return false;
        $src = @imagecreatefromstring((string)file_get_contents($sourceFile));
        if ($src === false) return false;
        $card = imagecreatetruecolor(1200, 630);
        imagefill($card, 0, 0, imagecolorallocate($card, 255, 255, 255));
        [$w, $h] = [imagesx($src), imagesy($src)];
        $scale = min(900 / $w, 400 / $h);
        [$tw, $th] = [max(1, (int)round($w * $scale)), max(1, (int)round($h * $scale))];
        $scaled = imagecreatetruecolor($tw, $th);
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        imagefill($scaled, 0, 0, imagecolorallocatealpha($scaled, 0, 0, 0, 127));
        imagecopyresampled($scaled, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
        imagealphablending($card, true);
        imagecopy($card, $scaled, (int)((1200 - $tw) / 2), (int)((630 - $th) / 2), 0, 0, $tw, $th);
        $dir = dirname($destFile);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) return false;
        return imagepng($card, $destFile, 9);
    }

    /** Fits $src inside a $size square (10% padding), centred, on a transparent or solid background. */
    private function fit(\GdImage $src, int $size, ?array $background): \GdImage
    {
        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, $background ? imagecolorallocate($canvas, ...$background) : imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagealphablending($canvas, true);

        $inner = (int)round($size * 0.9);
        [$w, $h] = [imagesx($src), imagesy($src)];
        $scale = min($inner / $w, $inner / $h);
        [$tw, $th] = [max(1, (int)round($w * $scale)), max(1, (int)round($h * $scale))];
        // imagecopyresampled keeps the alpha channel and works on every GD build (imagescale's modes vary by host).
        $scaled = imagecreatetruecolor($tw, $th);
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        imagefill($scaled, 0, 0, imagecolorallocatealpha($scaled, 0, 0, 0, 127));
        imagecopyresampled($scaled, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
        imagealphablending($canvas, true);
        imagecopy($canvas, $scaled, (int)(($size - $tw) / 2), (int)(($size - $th) / 2), 0, 0, $tw, $th);
        imagesavealpha($canvas, true);
        return $canvas;
    }

    private function write(string $path, string $bytes): void
    {
        if (file_put_contents($path, $bytes, LOCK_EX) === false) throw new \RuntimeException("Could not write $path");
    }
}
