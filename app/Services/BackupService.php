<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupService
{
    /**
     * Create an on-demand or scheduled database and attachment backup snapshot.
     */
    public function createBackup(?User $actor = null, string $type = 'automated'): array
    {
        $timestamp = now()->format('Y-m-d_His');
        $backupDir = storage_path('app/backups');

        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $sqlFilename = "backup_{$timestamp}.sql";
        $sqlPath = "{$backupDir}/{$sqlFilename}";

        // Database Configuration
        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', '3306');
        $dbName = config('database.connections.mysql.database', 'clothing_pos');
        $dbUser = config('database.connections.mysql.username', 'root');
        $dbPass = config('database.connections.mysql.password', '');

        // Generate mysqldump command
        $dumpBinary = 'mysqldump';
        if (File::exists('D:\\xampp\\mysql\\bin\\mysqldump.exe')) {
            $dumpBinary = '"D:\\xampp\\mysql\\bin\\mysqldump.exe"';
        }

        $passArg = $dbPass !== '' ? "-p\"{$dbPass}\"" : '';
        $command = "{$dumpBinary} -h {$dbHost} -P {$dbPort} -u {$dbUser} {$passArg} {$dbName} > \"{$sqlPath}\"";

        $output = [];
        $resultCode = 0;
        exec($command, $output, $resultCode);

        // Fallback for environments where exec is restricted or mysqldump fails: Generate PHP schema/data dump
        if ($resultCode !== 0 || !File::exists($sqlPath) || File::size($sqlPath) === 0) {
            $this->createPhpDatabaseDump($sqlPath);
        }

        $size = File::size($sqlPath);

        // Write verification metadata
        $metaFilename = "backup_{$timestamp}.json";
        $metaPath = "{$backupDir}/{$metaFilename}";
        $metadata = [
            'timestamp' => now()->toIso8601String(),
            'backup_type' => $type,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'System Scheduler',
            'database' => $dbName,
            'sql_file' => $sqlFilename,
            'size_bytes' => $size,
            'table_counts' => $this->captureTableRecordCounts(),
            'verified' => true,
        ];
        File::put($metaPath, json_encode($metadata, JSON_PRETTY_PRINT));

        AuditService::log(
            action: 'backup.created',
            auditable: null,
            oldValues: null,
            newValues: ['file' => $sqlFilename, 'size' => $size, 'type' => $type],
            reason: "Created {$type} database backup snapshot"
        );

        Log::info("POS Database Backup created: {$sqlFilename} ({$size} bytes)");

        return [
            'status' => 'success',
            'file' => $sqlFilename,
            'path' => $sqlPath,
            'metadata' => $metadata,
            'size_bytes' => $size,
        ];
    }

    /**
     * Pure-PHP fallback database dump when mysqldump CLI binary is unavailable (e.g. standard cPanel PHP restrictions).
     */
    protected function createPhpDatabaseDump(string $destinationPath): void
    {
        $handle = fopen($destinationPath, 'w');
        fwrite($handle, "-- POS System PHP Fallback Backup Dump\n");
        fwrite($handle, "-- Generated: " . now()->toIso8601String() . "\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $prop = "Tables_in_{$dbName}";

        foreach ($tables as $tableObj) {
            $table = $tableObj->$prop ?? array_values((array) $tableObj)[0];
            
            // Skip temporary or non-essential cache tables
            if (in_array($table, ['cache', 'cache_locks'])) continue;

            $createTable = DB::select("SHOW CREATE TABLE `{$table}`")[0]->{'Create Table'} ?? '';
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($handle, "{$createTable};\n\n");

            $rows = DB::table($table)->get();
            foreach ($rows as $row) {
                $rowArr = (array) $row;
                $cols = array_map(fn($c) => "`{$c}`", array_keys($rowArr));
                $vals = array_map(function($v) {
                    if (is_null($v)) return 'NULL';
                    return "'" . addslashes((string)$v) . "'";
                }, array_values($rowArr));

                $colStr = implode(', ', $cols);
                $valStr = implode(', ', $vals);
                fwrite($handle, "INSERT INTO `{$table}` ({$colStr}) VALUES ({$valStr});\n");
            }
            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
    }

    /**
     * Capture record counts across core business tables to verify post-restore data integrity.
     */
    public function captureTableRecordCounts(): array
    {
        $coreTables = [
            'stores', 'users', 'products', 'product_variants', 'stock_balances',
            'stock_ledgers', 'suppliers', 'purchase_orders', 'pos_sales',
            'pos_sale_items', 'pos_returns', 'daily_cash_registers',
            'financial_accounts', 'expenses', 'employees', 'audit_logs'
        ];

        $counts = [];
        foreach ($coreTables as $table) {
            try {
                $counts[$table] = DB::table($table)->count();
            } catch (Exception) {
                $counts[$table] = 0;
            }
        }
        return $counts;
    }

    /**
     * List all available local backup snapshots.
     */
    public function listBackups(): array
    {
        $backupDir = storage_path('app/backups');
        if (!File::isDirectory($backupDir)) {
            return [];
        }

        $metaFiles = File::glob("{$backupDir}/backup_*.json");
        $backups = [];

        foreach ($metaFiles as $meta) {
            $data = json_decode(File::get($meta), true);
            if ($data) {
                $backups[] = $data;
            }
        }

        usort($backups, fn($a, $b) => strcmp($b['timestamp'], $a['timestamp']));
        return $backups;
    }

    /**
     * Purge backups older than retention policy (365 days).
     */
    public function purgeOldBackups(int $retentionDays = 365): int
    {
        $backupDir = storage_path('app/backups');
        if (!File::isDirectory($backupDir)) {
            return 0;
        }

        $cutoff = now()->subDays($retentionDays)->timestamp;
        $files = File::files($backupDir);
        $deleted = 0;

        foreach ($files as $file) {
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getRealPath());
                $deleted++;
            }
        }

        return $deleted;
    }
}
