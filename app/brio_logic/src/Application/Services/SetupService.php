<?php
declare(strict_types=1);

namespace Brio\Application\Services;

use Brio\Domain\Exceptions\ValidationException;
use Brio\Domain\Product\Product;
use Brio\Infrastructure\Database\Connection;
use Brio\Infrastructure\Database\Migrator;
use Brio\Infrastructure\Logging\Logger;
use Brio\Infrastructure\Persistence\PdoProductRepository;

/** Setup wizard step 1: connect, create the database, write config, migrate, seed. */
final class SetupService
{
    public function __construct(
        private readonly string $configDir,
        private readonly Migrator $migrator,
        private readonly string $seedDir,
    ) {}

    /** @return array{migrations:string[],seeded:int} */
    public function installDatabase(array $in): array
    {
        $host = trim((string)($in['host'] ?? '127.0.0.1')) ?: '127.0.0.1';
        $port = (int)($in['port'] ?? 3306) ?: 3306;
        $db = trim((string)($in['database'] ?? ''));
        $user = trim((string)($in['username'] ?? ''));
        $pass = (string)($in['password'] ?? '');

        $e = [];
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $db)) $e['database'][] = 'Use letters, numbers and underscores only.';
        if ($user === '') $e['username'][] = 'Enter the database username.';
        if (!preg_match('/^[A-Za-z0-9.\-_:]+$/', $host)) $e['host'][] = 'Enter a valid host name.';
        if ($e) throw new ValidationException($e);

        try {
            try {
                $server = Connection::make($host, $port, null, $user, $pass);
                $server->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            } catch (\PDOException) {
                // Shared hosting: users can't create databases. Fine if you already made it in the control panel.
            }
            $pdo = Connection::make($host, $port, $db, $user, $pass);
        } catch (\PDOException $ex) {
            Logger::error('Setup: database connection failed', ['error' => $ex->getMessage()]);
            throw new ValidationException(['connection' => ['Could not connect: ' . $ex->getMessage()]], 'Could not connect to MySQL with those details.');
        }

        $ran = $this->migrator->run($pdo);
        $seeded = $this->seed($pdo);
        $this->writeConfig(compact('host', 'port', 'db', 'user', 'pass'));
        Logger::info('Setup: database installed', ['database' => $db, 'migrations' => $ran, 'seeded' => $seeded]);
        return ['migrations' => $ran, 'seeded' => $seeded];
    }

    private function seed(\PDO $pdo): int
    {
        $repo = new PdoProductRepository($pdo);
        if ($repo->count() > 0) return 0;
        $rows = require $this->seedDir . '/products.php';
        foreach ($rows as $row) {
            $p = Product::fromInput($row);
            $p->image = $row['image'];
            $repo->save($p);
        }
        return count($rows);
    }

    private function writeConfig(array $c): void
    {
        $config = ['host' => $c['host'], 'port' => $c['port'], 'database' => $c['db'], 'username' => $c['user'], 'password' => $c['pass']];
        $php = "<?php\n// Written by the Brio setup wizard on " . date('Y-m-d H:i') . ". Keep this file private.\nreturn " . var_export($config, true) . ";\n";
        $file = $this->configDir . '/database.php';
        if (file_put_contents($file, $php, LOCK_EX) === false) {
            throw new \RuntimeException('Could not write ' . $file . '. Make the config folder writable.');
        }
        @chmod($file, 0600);
    }
}
