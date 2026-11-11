<?php
declare(strict_types=1);

namespace Brio\Http\Controllers\Admin;

use Brio\Domain\Product\Product;
use Brio\Http\Request;
use Brio\Http\Response;
use Brio\Http\Session;

/** Renders the admin shell; the table itself loads from the JSON API. */
final class DashboardController
{
    private const VIEWS = [
        'products' => ['title' => 'Product', 'sub' => 'Manage your products, track inventory, and grow your store'],
        'offers'   => ['title' => 'Daily Deals', 'sub' => 'Choose which products appear as offers on the home page and how their cards look'],
        'featured' => ['title' => 'Featured Products', 'sub' => 'Pick the products shown in the “Featured Products” section of the home page'],
    ];

    public function products(Request $r): void { $this->render('products'); }
    public function offers(Request $r): void { $this->render('offers'); }
    public function featured(Request $r): void { $this->render('featured'); }

    private function render(string $mode): void
    {
        Response::view('admin/products', [
            'mode' => $mode,
            'page' => self::VIEWS[$mode],
            'admin' => brio('auth')->user(),
            'csrf' => Session::csrfToken(),
            'categories' => Product::CATEGORIES,
            'tones' => Product::DEAL_TONES,
            'lowThreshold' => brio('products')->lowThreshold(),
        ]);
    }
}
