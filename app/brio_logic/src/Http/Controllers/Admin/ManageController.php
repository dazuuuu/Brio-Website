<?php
declare(strict_types=1);

namespace Brio\Http\Controllers\Admin;

use Brio\Http\Request;
use Brio\Http\Response;
use Brio\Http\Session;

/** Renders the admin shells for hero slides, pages, navigation, orders and settings. Data loads from the JSON API. */
final class ManageController
{
    public function hero(Request $r): void { $this->render('hero'); }
    public function pages(Request $r): void { $this->render('pages'); }
    public function navigation(Request $r): void { $this->render('navigation'); }
    public function orders(Request $r): void { $this->render('orders'); }
    public function settings(Request $r): void { $this->render('settings'); }

    public function reset(Request $r): void
    {
        try { $counts = brio('reset')->preview(); } catch (\Throwable) { $counts = ['products' => 0, 'orders' => 0]; }
        $this->render('reset', ['counts' => $counts]);
    }

    public function pageNew(Request $r): void { $this->render('page-edit', ['pageId' => 0]); }

    public function pageEdit(Request $r): void
    {
        try {
            brio('pages')->get($r->int('id'));
        } catch (\Brio\Domain\Exceptions\NotFoundException) {
            Response::view('admin/error', ['message' => 'That page no longer exists.'], 404);
            return;
        }
        $this->render('page-edit', ['pageId' => $r->int('id')]);
    }

    private function render(string $view, array $extra = []): void
    {
        Response::view('admin/' . $view, ['admin' => brio('auth')->user(), 'csrf' => Session::csrfToken()] + $extra);
    }
}
