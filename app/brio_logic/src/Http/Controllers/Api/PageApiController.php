<?php
declare(strict_types=1);

namespace Brio\Http\Controllers\Api;

use Brio\Domain\Page\SystemPages;
use Brio\Http\Request;
use Brio\Http\Response;

final class PageApiController
{
    public function index(Request $r): void
    {
        Response::json(brio('pages')->all(), 200, ['removed' => brio('systempages')->removed(), 'productPages' => SystemPages::PRODUCT_PAGES]);
    }

    // POST /api/admin/pages/restore  {key}
    public function restore(Request $r): void { Response::json(brio('systempages')->restore($r->get('key')), 201); }

    public function show(Request $r): void { Response::json(brio('pages')->get($r->int('id'))); }

    public function store(Request $r): void { Response::json(brio('pages')->create($r->input()), 201); }

    public function update(Request $r): void { Response::json(brio('pages')->update($r->int('id'), $r->input())); }

    public function destroy(Request $r): void
    {
        brio('pages')->delete($r->int('id'));
        Response::json(null);
    }
}
