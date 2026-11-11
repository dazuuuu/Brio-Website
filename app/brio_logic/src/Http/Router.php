<?php
declare(strict_types=1);

namespace Brio\Http;

/** Tiny router: "api/admin/products/{id}" patterns, per-route middleware. */
final class Router
{
    /** @var array<int, array{0:string,1:string,2:callable,3:callable[]}> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler, array $middleware = []): void
    {
        $this->routes[] = [$method, trim($pattern, '/'), $handler, $middleware];
    }

    public function get(string $p, callable $h, array $m = []): void { $this->add('GET', $p, $h, $m); }
    public function post(string $p, callable $h, array $m = []): void { $this->add('POST', $p, $h, $m); }
    public function patch(string $p, callable $h, array $m = []): void { $this->add('PATCH', $p, $h, $m); }
    public function delete(string $p, callable $h, array $m = []): void { $this->add('DELETE', $p, $h, $m); }

    /** @return bool false when no route matches the path */
    public function dispatch(Request $request): bool
    {
        $pathMatched = false;
        foreach ($this->routes as [$method, $pattern, $handler, $middleware]) {
            $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', $pattern) . '$#';
            if (!preg_match($regex, $request->path, $m)) continue;
            $pathMatched = true;
            if ($method !== $request->method) continue;

            $request->params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            foreach ($middleware as $mw) {
                if ($mw($request) === false) return true; // middleware already responded
            }
            $handler($request);
            return true;
        }
        if ($pathMatched) {
            $request->wantsJson() ? Response::error('Method not allowed.', 405) : http_response_code(405);
            return true;
        }
        return false;
    }
}
