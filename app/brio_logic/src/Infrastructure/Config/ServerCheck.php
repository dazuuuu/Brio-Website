<?php
declare(strict_types=1);

namespace Brio\Infrastructure\Config;

/** Quick "will this host run Brio?" checklist shown on the setup page. */
final class ServerCheck
{
    public const MIN_PHP = '8.1.0';

    /** @return array<int,array{label:string,ok:bool,hint:string}> */
    public static function run(): array
    {
        $checks = [[
            'label' => 'PHP ' . self::MIN_PHP . ' or newer (this server runs ' . PHP_VERSION . ')',
            'ok' => version_compare(PHP_VERSION, self::MIN_PHP, '>='),
            'hint' => 'Choose PHP 8.1 or newer in your hosting control panel (often under “Select PHP Version”).',
        ]];
        foreach (['pdo_mysql' => 'MySQL access (pdo_mysql)', 'mbstring' => 'Text handling (mbstring)', 'fileinfo' => 'Image checks (fileinfo)'] as $ext => $label) {
            $checks[] = ['label' => $label, 'ok' => extension_loaded($ext),
                         'hint' => "Enable the “{$ext}” extension in your hosting control panel."];
        }
        $dirs = [
            BRIO_ROOT . '/config' => 'app/brio_logic/config',
            BRIO_ROOT . '/storage' => 'app/brio_logic/storage',
            BRIO_ROOT . '/storage/logs' => 'app/brio_logic/storage/logs',
            BRIO_ROOT . '/storage/cache' => 'app/brio_logic/storage/cache',
            BRIO_PUBLIC . '/uploads' => 'uploads (in your public folder)',
        ];
        foreach ($dirs as $path => $label) {
            // A folder that doesn't exist yet is fine if its parent is writable (the app creates it).
            $ok = is_dir($path) ? is_writable($path) : is_writable(dirname($path));
            $checks[] = ['label' => "Can write to {$label}", 'ok' => $ok, 'hint' => "Set the folder permissions of “{$label}” to 755 (or 775) so the site can save files there."];
        }
        return $checks;
    }
}
