<?php
declare(strict_types=1);

namespace Brio\Http\Controllers\Api;

use Brio\Http\Request;
use Brio\Http\Response;

/** Admin: logo, dark logo, site icon and share image. */
final class BrandApiController
{
    // POST /api/admin/brand/{logo|logo-dark|share-image|icon}  (multipart: image)
    public function upload(Request $r, string $slot): void
    {
        $svc = brio('brand');
        Response::json($slot === 'icon' ? $svc->setIcon($r->file('image')) : $svc->setImage($slot, $r->file('image')));
    }

    // DELETE /api/admin/brand/{slot}
    public function clear(Request $r, string $slot): void
    {
        $svc = brio('brand');
        Response::json($slot === 'icon' ? $svc->clearIcon() : $svc->clearImage($slot));
    }
}
