<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SystemBackup;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class BackupDownloadController extends Controller
{
    public function download(Request $request, SystemBackup $backup, BackupService $backupService)
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $fullPath = storage_path('app/' . $backup->file_path);
        if (!File::exists($fullPath)) {
            $fullPath = storage_path('app/private/' . $backup->file_path);
        }

        if (!File::exists($fullPath)) {
            return redirect()->back()->with('error', 'Backup file does not exist on disk.');
        }

        // Record audit download log
        $backupService->logDownload($backup, $user->id);

        // Close PHP session early so other requests & pages don't block during download
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Clean any active output buffers so binary streaming is direct and fast
        if (ob_get_level()) {
            ob_end_clean();
        }

        $fileSize = File::size($fullPath);

        return response()->download($fullPath, $backup->file_name, [
            'Content-Type' => 'application/zip',
            'Content-Length' => (string)$fileSize,
            'Content-Disposition' => 'attachment; filename="' . $backup->file_name . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
