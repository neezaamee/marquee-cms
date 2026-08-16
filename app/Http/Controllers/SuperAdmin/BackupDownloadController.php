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
            return redirect()->back()->with('error', 'Backup file does not exist on disk.');
        }

        // Record audit download log
        $backupService->logDownload($backup, $user->id);

        return response()->download($fullPath, $backup->file_name, [
            'Content-Type' => 'application/zip',
        ]);
    }
}
