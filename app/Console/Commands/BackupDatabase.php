<?php

namespace App\Console\Commands;

use App\Models\PlatformOperationLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--output= : Custom destination path for backup}';

    protected $description = 'Generate a complete, compressed SQL backup of the database.';

    public function handle(): int
    {
        $this->info('Starting database backup...');

        $backupDir = storage_path('app/backups');
        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $filename = 'lexisone_backup_' . now()->format('Y_m_d_His') . '.sql.gz';
        $outputPath = $this->option('output') ?: $backupDir . DIRECTORY_SEPARATOR . $filename;

        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            $database = $connection->getDatabaseName();

            $tables = $connection->getSchemaBuilder()->getTableListing();
            $sql = "-- LexisOne Database Backup\n";
            $sql .= "-- Database: {$database}\n";
            $sql .= "-- Generated: " . now()->toIso8601String() . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            $bar = $this->output->createProgressBar(count($tables));
            $bar->start();

            foreach ($tables as $table) {
                // Get create table statement
                try {
                    $createRow = $connection->select("SHOW CREATE TABLE `{$table}`");
                    $createSql = $createRow[0]->{'Create Table'} ?? null;
                    if ($createSql) {
                        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                        $sql .= $createSql . ";\n\n";
                    }

                    // Dump rows
                    $rows = $connection->table($table)->get();
                    if ($rows->isNotEmpty()) {
                        foreach ($rows->chunk(100) as $chunk) {
                            $columns = array_keys((array) $chunk->first());
                            $escapedColumns = implode(', ', array_map(fn($col) => "`{$col}`", $columns));
                            $valuesList = [];

                            foreach ($chunk as $row) {
                                $rowValues = array_map(function ($val) use ($pdo) {
                                    if ($val === null) {
                                        return 'NULL';
                                    }

                                    return $pdo->quote((string) $val);
                                }, (array) $row);

                                $valuesList[] = '(' . implode(', ', $rowValues) . ')';
                            }

                            $sql .= "INSERT INTO `{$table}` ({$escapedColumns}) VALUES\n" . implode(",\n", $valuesList) . ";\n\n";
                        }
                    }
                } catch (\Throwable $e) {
                    $this->warn("Skipping table {$table}: {$e->getMessage()}");
                }
                $bar->advance();
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            $bar->finish();
            $this->newLine();

            $compressed = gzencode($sql, 9);
            file_put_contents($outputPath, $compressed);

            $fileSizeBytes = filesize($outputPath);
            $fileSizeKb = round($fileSizeBytes / 1024, 2);

            PlatformOperationLog::create([
                'action' => 'database backup created',
                'payload' => [
                    'file' => basename($outputPath),
                    'size_bytes' => $fileSizeBytes,
                    'tables_count' => count($tables),
                ],
                'occurred_at' => now(),
            ]);

            $this->info("Backup successfully generated at: {$outputPath} ({$fileSizeKb} KB)");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Backup failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
