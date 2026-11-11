<?php
// Core helpers: URLs, escaping, formatting, icons, component rendering.

function base_path(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    return rtrim($dir === '/' || $dir === '.' ? '' : $dir, '/');
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/** Kenyan Shilling, no decimals: "KSh 1,450". */
function money(float $amount): string
{
    return 'KSh ' . number_format(round($amount));
}

/** "14 Mar 2024" (matches the en-KE short date format). */
function format_date(string $iso): string
{
    return date('j M Y', strtotime($iso));
}

/** Render a component from /components/<name>.php with variables. */
function component(string $name, array $props = []): void
{
    $file = __DIR__ . '/../../components/' . $name . '.php';
    if (!is_file($file)) {
        echo '<!-- missing component: ' . e($name) . ' -->';
        return;
    }
    extract($props, EXTR_SKIP);
    include $file;
}

/** Inline stroke icon on a 24px grid (same set as the original UI). */
function icon(string $name, int $size = 22, string $class = ''): string
{
    $paths = [
        'search'  => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'user'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
        'cart'    => '<path d="M3 4h2.5l2.2 11h9.8l2-8H6.4"/><circle cx="9.5" cy="19.5" r="1.4"/><circle cx="17" cy="19.5" r="1.4"/>',
        'close'   => '<path d="M6 6l12 12M18 6 6 18"/>',
        'menu'    => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'plus'    => '<path d="M12 5v14M5 12h14"/>',
        'minus'   => '<path d="M5 12h14"/>',
        'trash'   => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'check'   => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'chevron-left'  => '<path d="m15 18-6-6 6-6"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
    ];
    $cls = $class ? ' class="' . e($class) . '"' : '';
    if ($name === 'leaf') {
        return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"' . $cls . '>'
            . '<path d="M4 20C4 10 10 4 20 4c0 10-6 16-16 16Z"/><path d="M5.5 18.5C9 14 12 11 16 8" stroke="#fff" stroke-width="1.6" fill="none" stroke-linecap="round"/></svg>';
    }
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"' . $cls . '>'
        . ($paths[$name] ?? '') . '</svg>';
}

/** Five stars with half-star support, plus an optional review count. */
function star_rating(float $rating, ?int $count = null): string
{
    $value = max(0, min(5, $rating));
    $star = '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9Z"/>';
    $html = '<div class="stars" role="img" aria-label="' . number_format($value, 1) . ' out of 5 stars">';
    for ($n = 1; $n <= 5; $n++) {
        $fill = $value >= $n ? 1 : ($value >= $n - 0.5 ? 0.5 : 0);
        $html .= '<span class="stars__star"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" class="stars__empty" aria-hidden="true">' . $star . '</svg>';
        if ($fill > 0) {
            $html .= '<span class="stars__fill" style="width:' . ($fill * 100) . '%"><svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" aria-hidden="true">' . $star . '</svg></span>';
        }
        $html .= '</span>';
    }
    if ($count !== null) $html .= '<span class="stars__count">(' . $count . ')</span>';
    return $html . '</div>';
}

/**
 * Safe "mini-markdown" for page-builder text: paragraphs, - lists, **bold**, *italic*, [text](url).
 * Everything is HTML-escaped first, so editors can never inject markup or scripts.
 */
function format_text(string $text): string
{
    $html = [];
    foreach (preg_split('/\n{2,}/', str_replace("\r", '', trim($text))) ?: [] as $para) {
        $lines = explode("\n", $para);
        $isList = !array_filter($lines, static fn($l) => !preg_match('/^\s*[-*] /', $l));
        $html[] = $isList
            ? '<ul>' . implode('', array_map(static fn($l) => '<li>' . format_inline((string)preg_replace('/^\s*[-*] /', '', $l)) . '</li>', $lines)) . '</ul>'
            : '<p>' . implode('<br>', array_map('format_inline', $lines)) . '</p>';
    }
    return implode("\n", $html);
}

function format_inline(string $s): string
{
    $s = e($s);
    $s = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', static function (array $m): string {
        $raw = html_entity_decode($m[2], ENT_QUOTES);
        if (!preg_match('#^(https?://|mailto:|tel:|/(?!/))#i', $raw)) return $m[0];
        $href = $raw[0] === '/' ? url(ltrim($raw, '/')) : $raw;
        $ext = str_starts_with($raw, 'http') ? ' target="_blank" rel="noopener"' : '';
        return '<a href="' . e($href) . '"' . $ext . '>' . $m[1] . '</a>';
    }, $s) ?? $s;
    $s = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $s) ?? $s;
    return preg_replace('/(?<!\*)\*(?![\s*])(.+?)(?<![\s*])\*(?!\*)/', '<em>$1</em>', $s) ?? $s;
}
