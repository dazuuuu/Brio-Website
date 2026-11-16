<?php
declare(strict_types=1);

namespace Brio\Http\Controllers\Api;

use Brio\Http\Request;
use Brio\Http\Response;

/** Shopper accounts. Everything except register/login needs a signed-in customer (see Middleware::customer). */
final class CustomerApiController
{
    // POST /api/customer/register
    public function register(Request $r): void
    {
        $svc = brio('customers');
        $customer = $svc->register($r->input());
        Response::json($customer->toArray() + ['mailSent' => $svc->mailSent], 201);
    }

    // POST /api/customer/resend-verification
    public function resendVerification(Request $r): void
    {
        $svc = brio('customers');
        $customer = $svc->current();
        if ($customer->isVerified()) { Response::json(['alreadyVerified' => true, 'sent' => true]); return; }
        Response::json(['alreadyVerified' => false, 'sent' => $svc->sendVerification($customer)]);
    }

    // POST /api/customer/login  {email, password}
    public function login(Request $r): void
    {
        Response::json(brio('customers')->login((string)$r->get('email', ''), (string)$r->get('password', ''))->toArray());
    }

    // POST /api/customer/google  {credential}  (the ID token from "Continue with Google")
    public function google(Request $r): void { Response::json(brio('customers')->loginWithGoogle((string)$r->get('credential', ''))->toArray()); }

    // POST /api/customer/logout
    public function logout(Request $r): void
    {
        brio('customers')->logout();
        Response::json(null);
    }

    // GET /api/customer/me
    public function me(Request $r): void { Response::json(brio('customers')->current()->toArray()); }

    // PATCH /api/customer/me  {firstName, lastName, phone, whatsapp, address}
    public function update(Request $r): void
    {
        $svc = brio('customers');
        Response::json($svc->updateDetails($svc->current(), $r->input())->toArray());
    }

    // POST /api/customer/password  {current, new, confirm}
    public function password(Request $r): void
    {
        $svc = brio('customers');
        $svc->changePassword($svc->current(), (string)$r->get('current', ''), (string)$r->get('new', ''), (string)$r->get('confirm', ''));
        Response::json(null);
    }

    // GET /api/customer/orders
    public function orders(Request $r): void { Response::json(brio('orders')->forCustomer((int)brio('customers')->current()->id)); }
}
