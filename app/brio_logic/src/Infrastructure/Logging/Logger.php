<?php
declare(strict_types=1);

namespace Brio\Infrastructure\Logging;

/** Daily log files in storage/logs (brio-YYYY-MM-DD.log). */
final class Logger
{
    private static string $dir = '';

    public static function init(string $dir): void
    {
        self::$dir = $dir;
    }

    public static function info(string $message, array $context = []): void { self::write('INFO', $message, $context); }
    public static function warning(string $message, array $context = []): void { self::write('WARNING', $message, $context); }
    public static function error(string $message, array $context = []): void { self::write('ERROR', $message, $context); }

    private static function write(string $level, string $message, array $context): void
    {
        if (self::$dir === '') return;
        $line = sprintf("[%s] %s: %s%s\n", date('Y-m-d H:i:s'), $level, $message,
            $context ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '');
        @file_put_contents(self::$dir . '/brio-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
