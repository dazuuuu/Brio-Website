<?php
declare(strict_types=1);

namespace Brio\Http\Controllers\Api;

use Brio\Domain\Exceptions\ValidationException;
use Brio\Http\Request;
use Brio\Http\Response;

final class SettingsApiController
{
    public function show(Request $r): void { Response::json(brio('settings')->forAdmin()); }

    public function save(Request $r): void
    {
        brio('settings')->save($r->input());
        Response::json(brio('settings')->forAdmin());
    }

    // POST /api/admin/settings/test-email  {to}
    public function testEmail(Request $r): void
    {
        $to = trim((string)$r->get('to', ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) throw new ValidationException(['to' => ['Enter the email address to send the test to.']]);
        $problem = brio('mailer')->test($to);
        if ($problem !== '') throw new ValidationException(['to' => [$problem]]);
        Response::json(['sent' => true]);
    }
}
