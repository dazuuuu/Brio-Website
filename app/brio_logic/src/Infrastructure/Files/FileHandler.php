<?php
declare(strict_types=1);

namespace Brio\Infrastructure\Files;

use Brio\Domain\Exceptions\ValidationException;
use Brio\Infrastructure\Logging\Logger;

/** Validates and stores uploaded images and videos under /public/uploads/<folder>. */
final class FileHandler
{
    /** @param array{dir:string,max_bytes:int,mime:array<string,string>} $config */
    public function __construct(private readonly string $publicRoot, private readonly array $config) {}

    /**
     * @param array $file one entry from $_FILES
     * @param string $folder sub-folder of /public/uploads (products, hero, pages)
     * @return string path relative to /public (e.g. "uploads/products/ab12.jpg")
     */
    public function storeImage(array $file, string $folder = 'products'): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new ValidationException(['image' => [$this->uploadProblem($file)]]);
        }
        if ($file['size'] > $this->config['max_bytes']) {
            throw new ValidationException(['image' => ['Images must be ' . round($this->config['max_bytes'] / 1048576) . ' MB or smaller.']]);
        }
        // Trust the bytes, not the filename or the browser's Content-Type.
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $ext = $this->config['mime'][$mime] ?? null;
        if ($ext === null || @getimagesize($file['tmp_name']) === false) {
            throw new ValidationException(['image' => ['Upload a JPG, PNG or WebP image.']]);
        }

        if (!preg_match('/^[a-z]{3,12}$/', $folder)) $folder = 'products';
        $dir = $this->publicRoot . '/' . $this->config['dir'] . '/' . $folder;
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new \RuntimeException('Upload folder is not writable.');
        $name = bin2hex(random_bytes(12)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) throw new \RuntimeException('Could not save the uploaded image.');

        Logger::info('Image uploaded', ['file' => $name, 'bytes' => $file['size']]);
        return $this->config['dir'] . '/' . $folder . '/' . $name;
    }

    /** Deletes a previously uploaded file. Bundled images under assets/ are never touched. @return bool true if a file was removed */
    public function delete(string $relative): bool
    {
        if (!str_starts_with($relative, $this->config['dir'] . '/')) return false;
        $path = realpath($this->publicRoot . '/' . $relative);
        $root = realpath($this->publicRoot . '/' . $this->config['dir']);
        return $path && $root && str_starts_with($path, $root . DIRECTORY_SEPARATOR) && is_file($path) && @unlink($path);
    }

    /**
     * Stores an image OR a short video (MP4/WebM) for an ad.
     * @return array{type:string,path:string}
     */
    public function storeMedia(array $file, string $folder = 'ads'): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new ValidationException(['media' => [$this->uploadProblem($file)]]);
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

        if (isset($this->config['mime'][$mime])) {
            try {
                return ['type' => 'image', 'path' => $this->storeImage($file, $folder)];
            } catch (ValidationException $e) {
                throw new ValidationException(['media' => $e->errors['image'] ?? ['The upload failed. Please try again.']]);
            }
        }
        $ext = $this->config['video_mime'][$mime] ?? null;
        if ($ext === null) {
            throw new ValidationException(['media' => ['Upload a JPG, PNG or WebP image, or an MP4 or WebM video.']]);
        }
        $max = (int)($this->config['video_max_bytes'] ?? 25 * 1048576);
        if ($file['size'] > $max) {
            throw new ValidationException(['media' => ['Videos must be ' . round($max / 1048576) . ' MB or smaller. Try compressing it first.']]);
        }
        $dir = $this->publicRoot . '/' . $this->config['dir'] . '/' . (preg_match('/^[a-z]{3,12}$/', $folder) ? $folder : 'ads');
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new \RuntimeException('Upload folder is not writable.');
        $name = bin2hex(random_bytes(12)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) throw new \RuntimeException('Could not save the uploaded video.');
        Logger::info('Video uploaded', ['file' => $name, 'bytes' => $file['size']]);
        return ['type' => 'video', 'path' => $this->config['dir'] . '/' . basename($dir) . '/' . $name];
    }

    /** A plain-English reason for a failed upload (the usual one on hosting is the server's size limit). */
    private function uploadProblem(array $file): string
    {
        return match ($file['error'] ?? UPLOAD_ERR_NO_FILE) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'That file is bigger than this server allows (' . ini_get('upload_max_filesize') . '). Use a smaller file, or ask your host to raise upload_max_filesize.',
            UPLOAD_ERR_NO_FILE => 'Choose a file to upload.',
            default => 'The upload failed. Please try again.',
        };
    }
}
