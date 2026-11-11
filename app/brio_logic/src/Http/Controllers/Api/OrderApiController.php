<?php
declare(strict_types=1);

namespace Brio\Http\Controllers\Api;

use Brio\Http\Request;
use Brio\Http\Response;

final class OrderApiController
{
    // POST /api/orders  (public: the checkout page)
    public function place(Request $r): void
    {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        Response::json(brio('orders')->place($r->input(), $ip), 201);
    }

    // GET /api/admin/orders?status=&page=
    public function index(Request $r): void
    {
        $result = brio('orders')->paginate($r->query('status'), (int)$r->query('page', 1), 15);
        Response::json($result['data'], 200, ['meta' => $result['meta'], 'counts' => $result['counts']]);
    }

    // PATCH /api/admin/orders/{id}  {status}
    public function update(Request $r): void { Response::json(brio('orders')->setStatus($r->int('id'), $r->get('status'))); }

    public function destroy(Request $r): void
    {
        brio('orders')->delete($r->int('id'));
        Response::json(null);
    }
}
