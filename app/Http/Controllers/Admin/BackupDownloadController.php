<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Backups\BackupManager;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Downloads a backup through a short-lived signed link from the backups page.
 */
class BackupDownloadController extends Controller
{
    public function __invoke(string $name, BackupManager $backups): BinaryFileResponse
    {
        try {
            $path = $backups->path($name);
        } catch (RuntimeException) {
            abort(404);
        }

        return response()->download($path, $name, ['Content-Type' => 'application/zip']);
    }
}
