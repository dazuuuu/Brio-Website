<?php
declare(strict_types=1);

namespace Brio\Http\Controllers\Api;

use Brio\Http\Request;
use Brio\Http\Response;

/** Admin CRUD for hero slides (multipart for uploads, JSON for quick toggles and ordering). */
final class HeroApiController
{
    public function index(Request $r): void { Response::json(brio('hero')->all()); }

    public function store(Request $r): void
    {
        Response::json(brio('hero')->create($r->input(), $r->file('image'), $r->file('mobileImage')), 201);
    }

    public function update(Request $r): void
    {
        Response::json(brio('hero')->update($r->int('id'), $r->input(), $r->file('image'), $r->file('mobileImage')));
    }

    public function destroy(Request $r): void
    {
        brio('hero')->delete($r->int('id'));
        Response::json(null);
    }

    public function reorder(Request $r): void
    {
        brio('hero')->reorder($r->get('ids'));
        Response::json(brio('hero')->all());
    }
}
