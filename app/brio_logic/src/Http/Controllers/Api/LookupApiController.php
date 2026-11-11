<?php
declare(strict_types=1);

namespace Brio\Http\Controllers\Api;

use Brio\Domain\Product\Product;
use Brio\Http\Request;
use Brio\Http\Response;

/** Data for the admin's link pickers and the image uploader. */
final class LookupApiController
{
    // GET /api/admin/link-options
    public function linkOptions(Request $r): void
    {
        Response::json([
            'categories' => array_map(static fn($slug, $label) => ['slug' => $slug, 'label' => $label], array_keys(Product::CATEGORIES), Product::CATEGORIES),
            'products' => brio('products')->options(),
            'pages' => brio('pages')->options(),
        ]);
    }

    // POST /api/admin/uploads  (multipart: image, folder=pages|hero)
    public function upload(Request $r): void
    {
        $folder = in_array($r->get('folder'), ['pages', 'hero'], true) ? $r->get('folder') : 'pages';
        $file = $r->file('image');
        if ($file === null) {
            throw new \Brio\Domain\Exceptions\ValidationException(['image' => ['Choose an image to upload.']]);
        }
        $path = brio('files')->storeImage($file, $folder);
        Response::json(['path' => $path]);
    }
}
