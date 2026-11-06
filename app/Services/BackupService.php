<?php

namespace App\Services;

use App\Models\BackupActivityLog;
use App\Models\SystemBackup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

class BackupService
{
    /**
     * Get target backup directory.
     */
    public function getBackupDirectory(): string
    {
        $dir = storage_path('app/backups');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }
        return $dir;
    }

    /**
     * Log a backup activity record.
     */
    public function logActivity(
        string $action,
        ?int $backupId = null,
        ?int $userId = null,
        ?string $fileName = null,
        ?string $description = null,
        string $status = 'success'
    ): BackupActivityLog {
        $userId = $userId ?? Auth::id();
        $ip = Request::ip();
        $userAgent = Request::userAgent();

        return BackupActivityLog::create([
            'system_backup_id' => $backupId,
            'user_id' => $userId,
            'action' => $action,
            'file_name' => $fileName,
            'description' => $description,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'status' => $status,
            'created_at' => now(),
        ]);
    }

    /**
     * Clean up any stale in-progress backups (e.g. interrupted or timed-out requests).
     */
    public function cleanupStaleBackups(int $staleMinutes = 15): int
    {
        $cutoff = now()->subMinutes($staleMinutes);
        $staleBackups = SystemBackup::where('status', 'in_progress')
            ->where('created_at', '<', $cutoff)
            ->get();

        $count = 0;
        foreach ($staleBackups as $backup) {
            $backup->update([
                'status' => 'failed',
                'notes' => 'Backup timed out or the generation process was interrupted.',
            ]);
            $this->logActivity(
                action: 'backup_created',
                backupId: $backup->id,
                userId: $backup->created_by,
                fileName: $backup->file_name,
                description: "Marked stale in-progress backup '{$backup->file_name}' as failed (timed out)",
                status: 'failed'
            );
            $count++;
        }
        return $count;
    }

    /**
     * Find native mysqldump executable path on the host system.
     */
    public function findMysqldumpPath(): ?string
    {
        if ($envPath = config('backup.mysqldump_path') ?: env('MYSQLDUMP_PATH')) {
            if (File::exists($envPath)) {
                return $envPath;
            }
        }

        $commonDirs = [
            'C:\\wamp64\\bin\\mysql',
            'C:\\laragon\\bin\\mysql',
            'C:\\xampp\\mysql\\bin',
            'D:\\wamp64\\bin\\mysql',
            'C:\\Program Files\\MySQL',
        ];

        foreach ($commonDirs as $baseDir) {
            if (File::isDirectory($baseDir)) {
                $files = File::glob($baseDir . '/*/bin/mysqldump.exe');
                if (!empty($files) && File::exists($files[0])) {
                    return $files[0];
                }
                if (File::exists($baseDir . '/mysqldump.exe')) {
                    return $baseDir . '/mysqldump.exe';
                }
            }
        }

        $lookup = PHP_OS_FAMILY === 'Windows' ? 'where mysqldump 2>NUL' : 'which mysqldump 2>/dev/null';
        $out = @shell_exec($lookup);
        if ($out) {
            $lines = explode("\n", trim($out));
            $candidate = trim($lines[0]);
            if (!empty($candidate) && File::exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Generate a new database or full backup archive.
     */
    public function createBackup(
        string $type = 'db_only',
        string $trigger = 'manual',
        ?int $userId = null,
        ?string $notes = null
    ): SystemBackup {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $timestamp = now()->format('Y-m-d_H-i-s');
        $fileName = "backup_{$timestamp}_{$type}.zip";
        $backupDir = $this->getBackupDirectory();
        $zipPath = $backupDir . '/' . $fileName;
        $tempSqlPath = $backupDir . '/temp_dump_' . $timestamp . '_' . uniqid() . '.sql';

        $backupRecord = SystemBackup::create([
            'file_name' => $fileName,
            'file_path' => 'backups/' . $fileName,
            'type' => $type,
            'file_size' => 0,
            'status' => 'in_progress',
            'trigger_type' => $trigger,
            'created_by' => $userId ?? Auth::id(),
            'notes' => $notes,
        ]);

        try {
            $this->dumpDatabaseToFile($tempSqlPath);
            
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \Exception("Cannot create zip archive at {$zipPath}");
            }

            $zip->addFile($tempSqlPath, 'database_dump.sql');

            if ($type === 'full') {
                $publicStoragePath = storage_path('app/public');
                if (File::exists($publicStoragePath)) {
                    $files = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($publicStoragePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                        \RecursiveIteratorIterator::SELF_FIRST
                    );

                    foreach ($files as $file) {
                        $relativePath = 'storage/public/' . substr($file->getPathname(), strlen($publicStoragePath) + 1);
                        if ($file->isDir()) {
                            $zip->addEmptyDir($relativePath);
                        } else {
                            $zip->addFile($file->getPathname(), $relativePath);
                        }
                    }
                }
            }

            $zip->close();
            @unlink($tempSqlPath);

            $fileSize = File::exists($zipPath) ? File::size($zipPath) : 0;

            $backupRecord->update([
                'file_size' => $fileSize,
                'status' => 'completed',
            ]);

            $this->logActivity(
                action: 'backup_created',
                backupId: $backupRecord->id,
                userId: $userId,
                fileName: $fileName,
                description: "Created {$type} backup archive ({$backupRecord->human_size})",
                status: 'success'
            );

            return $backupRecord;
        } catch (\Throwable $e) {
            if (File::exists($tempSqlPath)) {
                @unlink($tempSqlPath);
            }
            if (File::exists($zipPath)) {
                @unlink($zipPath);
            }

            $backupRecord->update(['status' => 'failed', 'notes' => $e->getMessage()]);

            $this->logActivity(
                action: 'backup_created',
                backupId: $backupRecord->id,
                userId: $userId,
                fileName: $fileName,
                description: "Failed to create {$type} backup: " . $e->getMessage(),
                status: 'failed'
            );

            throw $e;
        }
    }

    /**
     * Dump current database connection directly to a SQL file on disk.
     */
    public function dumpDatabaseToFile(string $targetSqlPath): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            $mysqldump = $this->findMysqldumpPath();
            if ($mysqldump) {
                $config = config('database.connections.mysql');
                $host = $config['host'] ?? '127.0.0.1';
                $port = $config['port'] ?? '3306';
                $database = $config['database'] ?? 'mcms';
                $username = $config['username'] ?? 'root';
                $password = $config['password'] ?? '';

                $cmd = sprintf(
                    '"%s" --host=%s --port=%s --user=%s %s %s --single-transaction --quick --skip-lock-tables --result-file="%s"',
                    $mysqldump,
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($username),
                    $password !== '' ? '--password=' . escapeshellarg($password) : '',
                    escapeshellarg($database),
                    $targetSqlPath
                );

                @exec($cmd, $output, $returnCode);

                if ($returnCode === 0 && File::exists($targetSqlPath) && File::size($targetSqlPath) > 0) {
                    return;
                }
            }
        }

        // Fast streaming PHP fallback for SQLite or if mysqldump is unavailable
        $this->dumpDatabaseViaPhpStream($targetSqlPath);
    }

    /**
     * Stream database contents to a SQL file using chunked PDO queries and buffered writing.
     */
    public function dumpDatabaseViaPhpStream(string $targetSqlPath): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $pdo = $connection->getPdo();

        $handle = fopen($targetSqlPath, 'w');
        if (!$handle) {
            throw new \Exception("Cannot open file for writing: {$targetSqlPath}");
        }

        fwrite($handle, "-- Marquee CMS Database Backup\n");
        fwrite($handle, "-- Generated: " . now()->toIso8601String() . "\n");
        fwrite($handle, "-- Driver: {$driver}\n\n");

        if ($driver === 'sqlite') {
            $tablesQuery = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tables = $tablesQuery->fetchAll(\PDO::FETCH_COLUMN);

            fwrite($handle, "PRAGMA foreign_keys = OFF;\n\n");

            foreach ($tables as $table) {
                $stmt = $pdo->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?");
                $stmt->execute([$table]);
                $createSql = $stmt->fetchColumn();

                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($handle, $createSql . ";\n\n");

                $this->streamTableRows($handle, $connection, $pdo, $table);
                fwrite($handle, "\n");
            }

            fwrite($handle, "PRAGMA foreign_keys = ON;\n");
        } else {
            // MySQL / MariaDB
            $tablesQuery = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            $tables = $tablesQuery->fetchAll(\PDO::FETCH_COLUMN);

            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

            foreach ($tables as $table) {
                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $createRow = $createStmt->fetch(\PDO::FETCH_NUM);
                $createSql = $createRow[1] ?? '';

                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($handle, $createSql . ";\n\n");

                $this->streamTableRows($handle, $connection, $pdo, $table);
                fwrite($handle, "\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
        }

        fclose($handle);
    }

    /**
     * Stream rows from a table in multi-row batches directly to file handle.
     */
    protected function streamTableRows($handle, $connection, $pdo, string $table): void
    {
        $batch = [];
        $columns = null;

        foreach ($connection->table($table)->cursor() as $row) {
            $rowArray = (array) $row;
            if ($columns === null) {
                $columns = array_map(fn($c) => "`{$c}`", array_keys($rowArray));
            }

            $vals = array_map(function ($val) use ($pdo) {
                if ($val === null) return 'NULL';
                return $pdo->quote($val);
            }, array_values($rowArray));

            $batch[] = "(" . implode(', ', $vals) . ")";

            if (count($batch) >= 100) {
                fwrite($handle, "INSERT INTO `{$table}` (" . implode(', ', $columns) . ") VALUES\n" . implode(",\n", $batch) . ";\n");
                $batch = [];
            }
        }

        if (!empty($batch) && $columns !== null) {
            fwrite($handle, "INSERT INTO `{$table}` (" . implode(', ', $columns) . ") VALUES\n" . implode(",\n", $batch) . ";\n");
        }
    }

    /**
     * Dump current database connection to raw SQL string (PDO compatible for MySQL and SQLite).
     */
    public function dumpDatabaseToSql(): string
    {
        $tempPath = $this->getBackupDirectory() . '/temp_inspect_' . uniqid() . '.sql';
        $this->dumpDatabaseToFile($tempPath);
        $content = File::get($tempPath);
        @unlink($tempPath);
        return $content;
    }

    /**
     * Log download of a backup.
     */
    public function logDownload(SystemBackup $backup, ?int $userId = null): void
    {
        $this->logActivity(
            action: 'backup_downloaded',
            backupId: $backup->id,
            userId: $userId,
            fileName: $backup->file_name,
            description: "Downloaded backup archive '{$backup->file_name}' ({$backup->human_size})",
            status: 'success'
        );
    }

    /**
     * Restore database from a backup zip or raw sql file path.
     */
    public function restoreBackup(string $filePath, ?int $userId = null): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $tempExtractDir = storage_path('app/backups/temp_restore_' . time());
        $sqlPath = null;

        try {
            if ($extension === 'zip') {
                File::makeDirectory($tempExtractDir, 0755, true, true);
                $zip = new \ZipArchive();
                if ($zip->open($filePath) !== true) {
                    throw new \Exception("Unable to open backup zip file.");
                }

                $zip->extractTo($tempExtractDir);
                $zip->close();

                $sqlPath = $tempExtractDir . '/database_dump.sql';
                if (!File::exists($sqlPath)) {
                    // Try to find any .sql file inside zip
                    $sqlFiles = File::glob($tempExtractDir . '/*.sql');
                    if (!empty($sqlFiles)) {
                        $sqlPath = $sqlFiles[0];
                    } else {
                        throw new \Exception("No SQL dump file found inside zip archive.");
                    }
                }

                // If storage media exists inside zip, restore public files
                $zipStorageDir = $tempExtractDir . '/storage/public';
                if (File::exists($zipStorageDir)) {
                    File::copyDirectory($zipStorageDir, storage_path('app/public'));
                }
            } elseif ($extension === 'sql') {
                $sqlPath = $filePath;
            } else {
                throw new \Exception("Unsupported backup file extension: {$extension}");
            }

            $sqlContent = File::get($sqlPath);
            if (empty(trim($sqlContent))) {
                throw new \Exception("Backup SQL file is empty.");
            }

            // Execute SQL statements
            DB::connection()->unprepared($sqlContent);

            $this->logActivity(
                action: 'backup_restored',
                backupId: null,
                userId: $userId,
                fileName: basename($filePath),
                description: "Restored system database from archive '" . basename($filePath) . "'",
                status: 'success'
            );

            return true;
        } catch (\Throwable $e) {
            $this->logActivity(
                action: 'backup_restored',
                backupId: null,
                userId: $userId,
                fileName: basename($filePath),
                description: "Failed restoring database from '" . basename($filePath) . "': " . $e->getMessage(),
                status: 'failed'
            );

            throw $e;
        } finally {
            if (File::exists($tempExtractDir)) {
                File::deleteDirectory($tempExtractDir);
            }
        }
    }

    /**
     * Delete a backup file and its record.
     */
    public function deleteBackup(SystemBackup $backup, ?int $userId = null): bool
    {
        $fullPath = storage_path('app/' . $backup->file_path);
        if (File::exists($fullPath)) {
            File::delete($fullPath);
        } else {
            $privatePath = storage_path('app/private/' . $backup->file_path);
            if (File::exists($privatePath)) {
                File::delete($privatePath);
            }
        }

        $fileName = $backup->file_name;
        $backupId = $backup->id;

        $this->logActivity(
            action: 'backup_deleted',
            backupId: $backupId,
            userId: $userId,
            fileName: $fileName,
            description: "Deleted backup archive '{$fileName}'",
            status: 'success'
        );

        $backup->delete();

        return true;
    }

    /**
     * Delete backups older than specified retention days.
     */
    public function cleanExpiredBackups(int $retentionDays = 30): int
    {
        if ($retentionDays <= 0) {
            return 0;
        }

        $cutoffDate = now()->subDays($retentionDays);
        $expiredBackups = SystemBackup::where('created_at', '<', $cutoffDate)->get();

        $count = 0;
        foreach ($expiredBackups as $backup) {
            $this->deleteBackup($backup);
            $count++;
        }

        return $count;
    }
}
