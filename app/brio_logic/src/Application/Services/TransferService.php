<?php
declare(strict_types=1);

namespace Brio\Application\Services;

use Brio\Domain\Exceptions\ValidationException;
use Brio\Infrastructure\Logging\Logger;
use PDO;

/**
 * Moves the store's content (products, hero slides, pages, menus, ads, settings and the uploaded pictures)
 * from one copy of the site to another, e.g. your local test site to the live one, as a single .zip file.
 * Orders, customers and admin accounts are never exported or touched.
 */
final class TransferService
{
    /** Tables that make up the store's content, in the order they are written. */
    private const TABLES = ['products', 'hero_slides', 'pages', 'nav_items', 'ads', 'settings'];

    /** Settings that belong to one particular site and must not travel (mail password, Google ID, icon stamp). */
    private const SKIP_SETTINGS = ['smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_pass', 'mail_from', 'google_client_id', 'site_icon'];

    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm', 'ico'];

    public function __construct(private readonly PDO $pdo, private readonly string $publicDir) {}

    /** Builds the package and returns the path of a temporary .zip (the caller deletes it after sending). */
    public function export(): string
    {
        if (!class_exists(\ZipArchive::class)) throw new ValidationException(['package' => ['This server cannot create zip files (the PHP zip extension is off).']]);
        $data = ['format' => 'brio-transfer', 'version' => 1, 'exportedAt' => date('c'), 'tables' => []];
        foreach (self::TABLES as $t) {
            $rows = $this->pdo->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
            if ($t === 'settings') $rows = array_values(array_filter($rows, static fn($r) => !in_array($r['name'], self::SKIP_SETTINGS, true)));
            $data['tables'][$t] = $rows;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'brio');
        $zip = new \ZipArchive();
        if ($zip->open($tmp, \ZipArchive::OVERWRITE) !== true) throw new \RuntimeException('Could not create the export file.');
        $zip->addFromString('data.json', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE));

        $root = realpath($this->publicDir . '/uploads');
        if ($root !== false) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (!$file->isFile()) continue;
                $rel = 'uploads/' . str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                if (str_starts_with($rel, 'uploads/brand/icons/') || !$this->pathOk($rel)) continue; // icons are regenerated on the other site
                $zip->addFile($file->getPathname(), 'files/' . $rel);
            }
        }
        $zip->close();
        Logger::info('Content exported');
        return $tmp;
    }

    /**
     * Replaces this site's products, slides, pages, menus, ads and settings with the package's, and copies its pictures.
     * @return array<string,int> how many rows of each kind were imported
     */
    public function import(string $zipPath): array
    {
        if (!class_exists(\ZipArchive::class)) throw new ValidationException(['package' => ['This server cannot read zip files (the PHP zip extension is off).']]);
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) throw new ValidationException(['package' => ['That is not a valid export file.']]);
        $json = $zip->getFromName('data.json');
        $data = $json !== false ? json_decode($json, true) : null;
        if (!is_array($data) || ($data['format'] ?? '') !== 'brio-transfer' || !is_array($data['tables'] ?? null)) {
            $zip->close();
            throw new ValidationException(['package' => ['That file was not made by this store’s Export button.']]);
        }

        $counts = [];
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach (self::TABLES as $t) {
                $rows = $data['tables'][$t] ?? null;
                if (!is_array($rows)) continue;
                $counts[$t] = $this->replaceTable($t, $rows);
            }
        } finally {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        // Pictures and videos
        $files = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string)$zip->getNameIndex($i);
            if (!str_starts_with($name, 'files/')) continue;
            $rel = substr($name, 6);
            if (!$this->pathOk($rel)) continue;
            $dest = $this->publicDir . '/' . $rel;
            if (!is_dir(dirname($dest)) && !@mkdir(dirname($dest), 0755, true)) continue;
            $in = $zip->getStream($name);
            if (!$in) continue;
            $out = fopen($dest, 'wb');
            stream_copy_to_stream($in, $out);
            fclose($in);
            fclose($out);
            $files++;
        }
        $zip->close();
        $counts['files'] = $files;

        // Cached "already created" markers must not hide the imported pages.
        foreach (['system-pages.seeded'] as $m) @file_put_contents(dirname(__DIR__, 3) . '/storage/cache/' . $m, '');
        Logger::info('Content imported', $counts);
        return $counts;
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function replaceTable(string $table, array $rows): int
    {
        $live = $this->pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
        if ($table === 'settings') {
            // Only overwrite the settings in the package; keep this site's own mail/Google/icon settings.
            $this->pdo->exec('DELETE FROM `settings` WHERE `name` NOT IN (' . implode(',', array_map(fn($k) => $this->pdo->quote($k), self::SKIP_SETTINGS)) . ')');
        } else {
            $this->pdo->exec("DELETE FROM `$table`");
        }
        $n = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            if ($table === 'settings' && in_array($row['name'] ?? '', self::SKIP_SETTINGS, true)) continue;
            $cols = array_values(array_filter(array_keys($row), static fn($c) => in_array($c, $live, true)));
            if (!$cols) continue;
            $sql = "INSERT INTO `$table` (" . implode(',', array_map(static fn($c) => "`$c`", $cols)) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
            $this->pdo->prepare($sql)->execute(array_map(static fn($c) => is_scalar($row[$c]) || $row[$c] === null ? $row[$c] : json_encode($row[$c]), $cols));
            $n++;
        }
        return $n;
    }

    /** Only plain picture/video files inside uploads/ (blocks "../" tricks and scripts). */
    private function pathOk(string $rel): bool
    {
        if (!preg_match('#^uploads/[A-Za-z0-9._/-]+$#', $rel) || str_contains($rel, '..') || str_contains($rel, '//')) return false;
        return in_array(strtolower(pathinfo($rel, PATHINFO_EXTENSION)), self::ALLOWED_EXT, true);
    }
}
