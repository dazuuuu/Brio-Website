<?php
declare(strict_types=1);

namespace Brio\Http\Controllers\Api;

use Brio\Http\Request;
use Brio\Http\Response;

final class AdApiController
{
    // GET /api/admin/ads
    public function index(Request $r): void { Response::json(brio('ads')->all()); }

    // POST /api/admin/ads  (multipart: fields + optional "media")
    public function store(Request $r): void { Response::json(brio('ads')->create($r->input(), $r->file('media')), 201); }

    // POST /api/admin/ads/{id} (multipart edit) · PATCH (JSON, e.g. {"isActive": false})
    public function update(Request $r): void { Response::json(brio('ads')->update($r->int('id'), $r->input(), $r->file('media'))); }

    public function destroy(Request $r): void
    {
        brio('ads')->delete($r->int('id'));
        Response::json(null);
    }

    public function reorder(Request $r): void
    {
        brio('ads')->reorder($r->get('ids'));
        Response::json(brio('ads')->all());
    }

    // POST /api/ads/event  {id, type: view|click}  (public: the pop-up reporting what shoppers do)
    public function event(Request $r): void
    {
        brio('ads')->record((int)$r->get('id', 0), $r->get('type'));
        Response::json(null);
    }
}
