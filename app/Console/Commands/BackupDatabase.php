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
            $gz = gzopen($outputPath, 'wb9');
            if (! $gz) {
                throw new \RuntimeException("Cannot open {$outputPath} for writing.");
            }

            gzwrite($gz, "-- LexisOne Database Backup\n");
            gzwrite($gz, "-- Database: {$database}\n");
            gzwrite($gz, '-- Generated: ' . now()->toIso8601String() . "\n\n");
            gzwrite($gz, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            $bar = $this->output->createProgressBar(count($tables));
            $bar->start();

            foreach ($tables as $table) {
                // Get create table statement
                try {
                    $createRow = $connection->select("SHOW CREATE TABLE `{$table}`");
                    $createSql = $createRow[0]->{'Create Table'} ?? null;
                    if ($createSql) {
                        gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n");
                        gzwrite($gz, $createSql . ";\n\n");
                    }

                    // Dump rows using streaming cursor
                    $buffer = [];
                    foreach ($connection->table($table)->cursor() as $row) {
                        $buffer[] = $row;
                        if (count($buffer) >= 100) {
                            $this->writeInsertChunk($gz, $table, $buffer, $pdo);
                            $buffer = [];
                        }
                    }

                    if (! empty($buffer)) {
                        $this->writeInsertChunk($gz, $table, $buffer, $pdo);
                        $buffer = [];
                    }
                } catch (\Throwable $e) {
                    $this->warn("Skipping table {$table}: {$e->getMessage()}");
                }
                $bar->advance();
            }

            gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
            $bar->finish();
            $this->newLine();
            gzclose($gz);

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

    /**
     * @param  resource  $gz
     * @param  array<int, object>  $rows
     */
    private function writeInsertChunk($gz, string $table, array $rows, \PDO $pdo): void
    {
        $columns = array_keys((array) $rows[0]);
        $escapedColumns = implode(', ', array_map(fn($col) => "`{$col}`", $columns));
        $valuesList = [];

        foreach ($rows as $row) {
            $rowValues = array_map(function ($val) use ($pdo) {
                if ($val === null) {
                    return 'NULL';
                }

                return $pdo->quote((string) $val);
            }, (array) $row);

            $valuesList[] = '(' . implode(', ', $rowValues) . ')';
        }

        gzwrite($gz, "INSERT INTO `{$table}` ({$escapedColumns}) VALUES\n" . implode(",\n", $valuesList) . ";\n\n");
    }
}
