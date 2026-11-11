<?php
declare(strict_types=1);

namespace Brio\Http;

final class Request
{
    private ?array $json = null;

    public function __construct(
        public readonly string $method,
        public readonly string $path,      // e.g. "api/admin/products/3" (no leading slash, no base path)
        public array $params = [],          // route parameters
    ) {}

    public static function capture(string $path): self
    {
        return new self(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), trim($path, '/'));
    }

    public function query(string $key, mixed $default = null): mixed { return $_GET[$key] ?? $default; }

    public function allQuery(): array { return $_GET; }

    /** Form fields or JSON body, whichever was sent. */
    public function input(): array
    {
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            return $this->json ??= (json_decode((string)file_get_contents('php://input'), true) ?: []);
        }
        return $_POST;
    }

    public function get(string $key, mixed $default = null): mixed { return $this->input()[$key] ?? $default; }

    public function file(string $key): ?array { return $_FILES[$key] ?? null; }

    public function header(string $name): ?string
    {
        return $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $name))] ?? null;
    }

    public function wantsJson(): bool { return str_starts_with($this->path, 'api/'); }

    public function int(string $param): int { return (int)($this->params[$param] ?? 0); }
}
