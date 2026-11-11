<?php
declare(strict_types=1);

namespace Brio\Infrastructure\Database;

use Brio\Infrastructure\Logging\Logger;
use PDO;

/** Runs database/migrations/*.sql in filename order, once each (tracked in `migrations`). */
final class Migrator
{
    /** @param string|null $marker file remembering which migration set was applied (lets us skip the check) */
    public function __construct(private readonly string $dir, private readonly ?string $marker = null) {}

    /** Cheap on every request: runs only when new .sql files appeared since the last run. */
    public function runIfPending(PDO $pdo): void
    {
        if ($this->marker !== null && is_file($this->marker) && trim((string)file_get_contents($this->marker)) === $this->signature()) return;
        $this->run($pdo);
    }

    private function signature(): string
    {
        return md5(implode('|', array_map('basename', glob($this->dir . '/*.sql') ?: [])));
    }

    /** @return string[] names of the migrations that ran now */
    public function run(PDO $pdo): array
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(190) NOT NULL UNIQUE,
            ran_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $done = $pdo->query('SELECT name FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
        $files = glob($this->dir . '/*.sql') ?: [];
        sort($files);

        $ran = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $done, true)) continue;
            // MySQL DDL auto-commits, so run statement by statement and record on success.
            foreach (array_filter(array_map('trim', explode(';', (string)file_get_contents($file)))) as $sql) {
                $pdo->exec($sql);
            }
            $pdo->prepare('INSERT INTO migrations (name) VALUES (?)')->execute([$name]);
            Logger::info('Migration ran', ['migration' => $name]);
            $ran[] = $name;
        }
        if ($this->marker !== null) @file_put_contents($this->marker, $this->signature(), LOCK_EX);
        return $ran;
    }
}
