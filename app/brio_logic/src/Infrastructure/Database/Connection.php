<?php
declare(strict_types=1);

namespace Brio\Infrastructure\Database;

use PDO;

final class Connection
{
    /** @param array{host:string,port:int|string,database:string,username:string,password:string}|null $c */
    public static function fromConfig(?array $c): PDO
    {
        if (!$c) throw new \RuntimeException('Database is not configured. Run the setup wizard at /admin/setup.');
        return self::make($c['host'], (int)$c['port'], $c['database'], $c['username'], $c['password']);
    }

    /** $database = null connects to the server only (used to CREATE DATABASE during setup). */
    public static function make(string $host, int $port, ?string $database, string $user, string $pass): PDO
    {
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4" . ($database ? ";dbname=$database" : '');
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ]);
    }
}
