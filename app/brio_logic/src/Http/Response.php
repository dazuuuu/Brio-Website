<?php
declare(strict_types=1);

namespace Brio\Http;

final class Response
{
    /** API envelope: { success, data, message?, errors?, meta? } — same shape the React UI expected. */
    public static function json(mixed $data, int $status = 200, array $extra = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['success' => $status < 400, 'data' => $data] + $extra, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function error(string $message, int $status, array $errors = []): void
    {
        self::json(null, $status, ['message' => $message] + ($errors ? ['errors' => $errors] : []));
    }

    public static function notFound(): void
    {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><title>404 Not Found</title><h1>Not Found</h1><p>The requested URL was not found on this server.</p>';
    }

    public static function redirect(string $to): void
    {
        header('Location: ' . $to, true, 303);
    }

    /** Renders views/<name>.php with $data extracted into scope. */
    public static function view(string $name, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');
        extract($data, EXTR_SKIP);
        require BRIO_ROOT . '/views/' . $name . '.php';
    }
}
