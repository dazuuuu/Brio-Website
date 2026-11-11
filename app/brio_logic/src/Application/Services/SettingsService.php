<?php
declare(strict_types=1);

namespace Brio\Application\Services;

use Brio\Domain\Exceptions\ValidationException;
use Brio\Domain\Settings\SettingsRepository;
use Brio\Infrastructure\Logging\Logger;

/** Store-wide settings editable from the admin. */
final class SettingsService
{
    private const DEFAULTS = [
        'whatsapp_number' => '',
        'whatsapp_greeting' => 'Hello! I would like to place an order.',
        'show_search' => '1',
        'show_account_icon' => '1',
    ];

    public function __construct(private readonly SettingsRepository $repo) {}

    /** @return array<string,string> */
    public function get(): array
    {
        return array_intersect_key($this->repo->all() + self::DEFAULTS, self::DEFAULTS) + self::DEFAULTS;
    }

    /** Same values as booleans, for templates. */
    public function flags(): array
    {
        $s = $this->get();
        return ['showSearch' => $s['show_search'] === '1', 'showAccountIcon' => $s['show_account_icon'] === '1'];
    }

    /** Only the keys present in $in change; everything else keeps its current value. */
    public function save(array $in): array
    {
        $e = [];
        $new = [];

        if (array_key_exists('whatsapp_number', $in)) {
            $digits = preg_replace('/\D/', '', (string)$in['whatsapp_number']) ?? '';
            $digits = preg_replace('/^00/', '', $digits) ?? '';
            if ($digits !== '' && (strlen($digits) < 8 || strlen($digits) > 15)) {
                $e['whatsapp_number'][] = 'Enter the number with its country code, e.g. +254 700 123 456.';
            }
            $new['whatsapp_number'] = $digits;
        }
        if (array_key_exists('whatsapp_greeting', $in)) {
            $greeting = trim((string)$in['whatsapp_greeting']);
            if (mb_strlen($greeting) > 300) $e['whatsapp_greeting'][] = 'Keep the greeting under 300 characters.';
            $new['whatsapp_greeting'] = $greeting !== '' ? $greeting : self::DEFAULTS['whatsapp_greeting'];
        }
        foreach (['show_search', 'show_account_icon'] as $flag) {
            if (array_key_exists($flag, $in)) $new[$flag] = filter_var($in[$flag], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }
        if ($e) throw new ValidationException($e);

        if ($new) $this->repo->set($new);
        Logger::info('Settings saved', ['fields' => array_keys($new)]);
        return $this->get();
    }
}
