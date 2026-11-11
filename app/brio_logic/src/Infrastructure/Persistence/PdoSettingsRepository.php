<?php
declare(strict_types=1);

namespace Brio\Infrastructure\Persistence;

use Brio\Domain\Settings\SettingsRepository;
use PDO;

final class PdoSettingsRepository implements SettingsRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function all(): array
    {
        return $this->pdo->query('SELECT name, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    }

    public function set(array $values): void
    {
        $existing = $this->all();
        $this->pdo->beginTransaction();
        foreach ($values as $name => $value) {
            $sql = array_key_exists($name, $existing) ? 'UPDATE settings SET value = ? WHERE name = ?' : 'INSERT INTO settings (value, name) VALUES (?, ?)';
            $this->pdo->prepare($sql)->execute([(string)$value, (string)$name]);
        }
        $this->pdo->commit();
    }
}
