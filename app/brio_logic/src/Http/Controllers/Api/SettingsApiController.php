<?php
declare(strict_types=1);

namespace Brio\Http\Controllers\Api;

use Brio\Http\Request;
use Brio\Http\Response;

final class SettingsApiController
{
    public function show(Request $r): void { Response::json(brio('settings')->get()); }

    public function save(Request $r): void { Response::json(brio('settings')->save($r->input())); }
}
