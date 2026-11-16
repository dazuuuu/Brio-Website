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
        'admin_accent' => '#f25c1f',
        // Brand: name, logos, icon and the picture shown when a page is shared
        'store_name' => 'Asiva Organics',
        'store_tagline' => 'Natural skin & body care',
        'logo' => '',              // uploaded logo path ('' = the bundled one)
        'logo_dark' => '',         // optional logo for the dark theme
        'site_icon' => '',         // version stamp once a custom icon has been generated
        'share_image' => 'popular', // popular | logo | custom
        'share_image_path' => '',
        'google_client_id' => '',
        // Outgoing email (account confirmation links)
        'smtp_host' => '',
        'smtp_port' => '465',
        'smtp_secure' => 'ssl',    // ssl | tls | none
        'smtp_user' => '',
        'smtp_pass' => '',         // write-only: never sent back to the browser
        'mail_from' => '',
    ];

    /** Keys set by file uploads (never typed into a form). */
    private const INTERNAL = ['logo', 'logo_dark', 'site_icon', 'share_image', 'share_image_path'];

    public function __construct(private readonly SettingsRepository $repo) {}

    /** @return array<string,string> */
    public function get(): array
    {
        return array_intersect_key($this->repo->all() + self::DEFAULTS, self::DEFAULTS) + self::DEFAULTS;
    }

    /** What the admin screen may see: everything except the mail password (only whether one is saved). */
    public function forAdmin(): array
    {
        $s = $this->get();
        $s['smtp_pass_set'] = $s['smtp_pass'] !== '';
        unset($s['smtp_pass']);
        return $s;
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
        if (array_key_exists('admin_accent', $in)) {
            $hex = strtolower(trim((string)$in['admin_accent']));
            if (!preg_match('/^#[0-9a-f]{6}$/', $hex)) $e['admin_accent'][] = 'Choose a valid colour.';
            $new['admin_accent'] = $hex;
        }
        if (array_key_exists('store_name', $in)) {
            $name = trim((string)$in['store_name']);
            if ($name === '' || mb_strlen($name) > 60) $e['store_name'][] = 'Enter your store name (up to 60 characters).';
            $new['store_name'] = $name;
        }
        if (array_key_exists('store_tagline', $in)) {
            $tag = trim((string)$in['store_tagline']);
            if (mb_strlen($tag) > 120) $e['store_tagline'][] = 'Keep the tagline under 120 characters.';
            $new['store_tagline'] = $tag;
        }
        if (array_key_exists('share_image', $in)) {
            $mode = (string)$in['share_image'];
            if (!in_array($mode, ['popular', 'logo', 'custom'], true)) $e['share_image'][] = 'Choose a share image option.';
            elseif ($mode === 'custom' && $this->get()['share_image_path'] === '') $e['share_image'][] = 'Upload your image first.';
            $new['share_image'] = $mode;
        }
        if (array_key_exists('google_client_id', $in)) {
            $id = trim((string)$in['google_client_id']);
            if ($id !== '' && !preg_match('/^[0-9]{6,}-[a-z0-9_]+\.apps\.googleusercontent\.com$/i', $id)) {
                $e['google_client_id'][] = 'That doesn’t look like a Google Client ID. It ends in .apps.googleusercontent.com.';
            }
            $new['google_client_id'] = $id;
        }
        if (array_key_exists('smtp_host', $in)) {
            $host = trim((string)$in['smtp_host']);
            if ($host !== '' && !preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/i', $host)) $e['smtp_host'][] = 'Enter the mail server name, e.g. smtp.hostinger.com.';
            $new['smtp_host'] = $host;
        }
        if (array_key_exists('smtp_port', $in)) {
            $port = trim((string)$in['smtp_port']);
            if ($port !== '' && (!ctype_digit($port) || (int)$port < 1 || (int)$port > 65535)) $e['smtp_port'][] = 'Enter a port number, e.g. 465 or 587.';
            $new['smtp_port'] = $port !== '' ? $port : '465';
        }
        if (array_key_exists('smtp_secure', $in)) {
            $sec = (string)$in['smtp_secure'];
            if (!in_array($sec, ['ssl', 'tls', 'none'], true)) $e['smtp_secure'][] = 'Choose SSL, TLS or none.';
            $new['smtp_secure'] = $sec;
        }
        if (array_key_exists('smtp_user', $in)) $new['smtp_user'] = mb_substr(trim((string)$in['smtp_user']), 0, 190);
        if (array_key_exists('smtp_pass', $in) && (string)$in['smtp_pass'] !== '') $new['smtp_pass'] = (string)$in['smtp_pass']; // blank keeps the saved one
        if (!empty($in['smtp_pass_clear'])) $new['smtp_pass'] = '';
        if (array_key_exists('mail_from', $in)) {
            $from = trim((string)$in['mail_from']);
            if ($from !== '' && !filter_var($from, FILTER_VALIDATE_EMAIL)) $e['mail_from'][] = 'Enter a valid email address, e.g. no-reply@yourstore.com.';
            $new['mail_from'] = $from;
        }
        foreach (['show_search', 'show_account_icon'] as $flag) {
            if (array_key_exists($flag, $in)) $new[$flag] = filter_var($in[$flag], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }
        if ($e) throw new ValidationException($e);

        if ($new) $this->repo->set($new);
        Logger::info('Settings saved', ['fields' => array_keys($new)]);
        return $this->get();
    }

    /** Used by the brand uploader to store file paths; not reachable from a form. @param array<string,string> $values */
    public function setInternal(array $values): array
    {
        $values = array_intersect_key($values, array_flip(self::INTERNAL));
        if ($values) $this->repo->set($values);
        return $this->get();
    }
}
