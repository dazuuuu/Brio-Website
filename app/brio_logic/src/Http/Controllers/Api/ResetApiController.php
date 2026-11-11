<?php
declare(strict_types=1);

namespace Brio\Http\Controllers\Api;

use Brio\Domain\Exceptions\ValidationException;
use Brio\Http\Request;
use Brio\Http\Response;

final class ResetApiController
{
    // POST /api/admin/reset  {password, confirm:true}
    public function run(Request $r): void
    {
        if (!filter_var($r->get('confirm'), FILTER_VALIDATE_BOOLEAN)) {
            throw new ValidationException(['confirm' => ['Tick the box to confirm.']], 'Tick the box to confirm.');
        }
        $password = (string)$r->get('password', '');
        if ($password === '') throw new ValidationException(['password' => ['Enter your password.']], 'Enter your password.');
        Response::json(brio('reset')->run($password));
    }
}
