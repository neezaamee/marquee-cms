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
     * Generate a new database or full backup archive.
     */
    public function createBackup(
        string $type = 'db_only',
        string $trigger = 'manual',
        ?int $userId = null,
        ?string $notes = null
    ): SystemBackup {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $fileName = "backup_{$timestamp}_{$type}.zip";
        $backupDir = $this->getBackupDirectory();
        $zipPath = $backupDir . '/' . $fileName;

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
            $sqlContent = $this->dumpDatabaseToSql();
            
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \Exception("Cannot create zip archive at {$zipPath}");
            }

            $zip->addFromString('database_dump.sql', $sqlContent);

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
     * Dump current database connection to raw SQL string (PDO compatible for MySQL and SQLite).
     */
    public function dumpDatabaseToSql(): string
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $pdo = $connection->getPdo();

        $sql = "-- Marquee CMS Database Backup\n";
        $sql .= "-- Generated: " . now()->toIso8601String() . "\n";
        $sql .= "-- Driver: {$driver}\n\n";

        if ($driver === 'sqlite') {
            $tablesQuery = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tables = $tablesQuery->fetchAll(\PDO::FETCH_COLUMN);

            $sql .= "PRAGMA foreign_keys = OFF;\n\n";

            foreach ($tables as $table) {
                // Fetch create table syntax
                $stmt = $pdo->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?");
                $stmt->execute([$table]);
                $createSql = $stmt->fetchColumn();

                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= $createSql . ";\n\n";

                // Fetch rows
                $rows = $connection->table($table)->get();
                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $cols = array_map(fn($c) => "`{$c}`", array_keys($rowArray));
                    $vals = array_map(function ($val) use ($pdo) {
                        if ($val === null) return 'NULL';
                        return $pdo->quote($val);
                    }, array_values($rowArray));

                    $sql .= "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
                }
                $sql .= "\n";
            }

            $sql .= "PRAGMA foreign_keys = ON;\n";
        } else {
            // MySQL / MariaDB
            $tablesQuery = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            $tables = $tablesQuery->fetchAll(\PDO::FETCH_COLUMN);

            $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

            foreach ($tables as $table) {
                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $createRow = $createStmt->fetch(\PDO::FETCH_NUM);
                $createSql = $createRow[1] ?? '';

                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= $createSql . ";\n\n";

                $rows = $connection->table($table)->get();
                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $cols = array_map(fn($c) => "`{$c}`", array_keys($rowArray));
                    $vals = array_map(function ($val) use ($pdo) {
                        if ($val === null) return 'NULL';
                        return $pdo->quote($val);
                    }, array_values($rowArray));

                    $sql .= "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
                }
                $sql .= "\n";
            }

            $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        }

        return $sql;
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
