<?php
declare(strict_types=1);

namespace Brio\Http\Controllers\Api;

use Brio\Http\Request;
use Brio\Http\Response;

final class NavApiController
{
    // GET /api/admin/nav?location=header
    public function index(Request $r): void
    {
        Response::json(brio('navigation')->items((string)$r->query('location', 'header')));
    }

    // POST /api/admin/nav  {location, items:[{label, linkType, linkTarget, newTab, isActive}]}
    public function save(Request $r): void
    {
        Response::json(brio('navigation')->save((string)$r->get('location', ''), $r->get('items')));
    }
}
