<?php
declare(strict_types=1);

namespace Brio\Http\Controllers\Api;

use Brio\Application\Services\ProductService;
use Brio\Http\Request;
use Brio\Http\Response;
use Brio\Infrastructure\Config\Config;

/** JSON endpoints for products: public catalogue + admin CRUD. */
final class ProductApiController
{
    public function __construct(private readonly ProductService $products) {}

    // GET /api/products?category=&q=&featured=1&deal=1
    public function index(Request $r): void
    {
        $flag = static fn($v) => $v === null || $v === '' ? null : filter_var($v, FILTER_VALIDATE_BOOLEAN);
        $data = $this->products->catalog((string)$r->query('category', 'all'), (string)$r->query('q', ''), $flag($r->query('featured')), $flag($r->query('deal')));
        Response::json($data, 200, ['meta' => ['total' => count($data)]]);
    }

    // GET /api/products/{id}
    public function show(Request $r): void
    {
        Response::json($this->products->get($r->int('id')));
    }

    // GET /api/admin/products?q=&stock=&featured=&deal=&sort=&dir=&page=
    public function adminIndex(Request $r): void
    {
        $perPage = (int)$r->query('per_page', Config::get('app.admin.per_page', 10));
        $result = $this->products->paginate($r->allQuery(), min(1000, max(1, $perPage)));
        Response::json($result['data'], 200, ['meta' => $result['meta']]);
    }

    // GET /api/admin/stats
    public function stats(Request $r): void
    {
        Response::json($this->products->stats());
    }

    // POST /api/admin/products (multipart: fields + optional "image")
    public function store(Request $r): void
    {
        Response::json($this->products->create($r->input(), $r->file('image')), 201);
    }

    // POST /api/admin/products/{id} (multipart edit, so images can be replaced)
    // PATCH /api/admin/products/{id} (JSON, e.g. {"isFeatured": true})
    public function update(Request $r): void
    {
        Response::json($this->products->update($r->int('id'), $r->input(), $r->file('image')));
    }

    // DELETE /api/admin/products/{id}
    public function destroy(Request $r): void
    {
        $this->products->delete($r->int('id'));
        Response::json(null);
    }
}
