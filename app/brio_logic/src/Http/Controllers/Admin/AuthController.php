<?php
declare(strict_types=1);

namespace Brio\Http\Controllers\Admin;

use Brio\Domain\Exceptions\AuthException;
use Brio\Http\Request;
use Brio\Http\Response;
use Brio\Http\Session;

final class AuthController
{
    public function show(Request $r): void
    {
        if (brio('auth')->user()) { Response::redirect(admin_url('products')); return; }
        Response::view('admin/login', ['error' => null, 'email' => '', 'notice' => Session::flash('notice')]);
    }

    public function login(Request $r): void
    {
        $email = (string)$r->get('email', '');
        try {
            brio('auth')->attempt($email, (string)$r->get('password', ''));
            Response::redirect(admin_url('products'));
        } catch (AuthException $e) {
            Response::view('admin/login', ['error' => $e->getMessage(), 'email' => $email, 'notice' => null], 401);
        }
    }

    public function logout(Request $r): void
    {
        brio('auth')->logout();
        Session::flash('notice', 'You have been signed out.');
        Response::redirect(admin_url('login'));
    }
}
