<?php
declare(strict_types=1);

namespace Brio\Infrastructure\Config;

/** Reads config/*.php files; "app.uploads.dir" style dotted keys. */
final class Config
{
    private static array $items = [];
    private static string $dir = '';

    public static function load(string $dir): void
    {
        self::$dir = $dir;
        foreach (['app', 'database'] as $name) {
            $file = "$dir/$name.php";
            self::$items[$name] = is_file($file) ? require $file : null;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) return $default;
            $value = $value[$part];
        }
        return $value;
    }

    /** True once the setup wizard has written database credentials. */
    public static function hasDatabase(): bool
    {
        return is_array(self::$items['database'] ?? null);
    }

    public static function reload(): void
    {
        if (function_exists('opcache_invalidate')) @opcache_invalidate(self::$dir . '/database.php', true);
        self::load(self::$dir);
    }
}
