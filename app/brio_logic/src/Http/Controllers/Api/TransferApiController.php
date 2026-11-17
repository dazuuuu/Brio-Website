<?php
declare(strict_types=1);

namespace Brio\Http\Controllers\Api;

use Brio\Domain\Exceptions\ValidationException;
use Brio\Http\Request;
use Brio\Http\Response;

/** Export / import of the store's content (products, pages, menus, ads, settings, pictures). */
final class TransferApiController
{
    // GET /api/admin/transfer/export  -> the .zip file
    public function export(Request $r): void
    {
        $path = brio('transfer')->export();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="store-content-' . date('Y-m-d') . '.zip"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store');
        readfile($path);
        @unlink($path);
    }

    // POST /api/admin/transfer/import  multipart: package (.zip), confirm=yes
    public function import(Request $r): void
    {
        if ((string)$r->get('confirm', '') !== 'yes') throw new ValidationException(['package' => ['Please confirm that you want to replace this site’s content.']]);
        $f = $_FILES['package'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $big = ($f['error'] ?? 0) === UPLOAD_ERR_INI_SIZE || ($f['error'] ?? 0) === UPLOAD_ERR_FORM_SIZE;
            throw new ValidationException(['package' => [$big ? 'That file is bigger than your host allows (' . ini_get('upload_max_filesize') . '). Ask your host to raise upload_max_filesize, or export fewer pictures.' : 'Choose the .zip file you exported.']]);
        }
        Response::json(brio('transfer')->import((string)$f['tmp_name']));
    }
}
