<?php

namespace App\Console\Commands;

use App\Models\PlatformOperationLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RestoreDatabase extends Command
{
    protected $signature = 'db:restore {file : Path or filename of the backup file} {--force : Bypass interactive confirmation}';

    protected $description = 'Safely restore the database from a compressed or plain SQL backup file.';

    public function handle(): int
    {
        $input = $this->argument('file');
        $filePath = File::exists($input)
            ? $input
            : storage_path('app/backups' . DIRECTORY_SEPARATOR . basename($input));

        if (! File::exists($filePath)) {
            $this->error("Backup file not found at: {$filePath}");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("WARNING: This will replace current database data with {$filePath}. Continue?")) {
            $this->info('Restore canceled.');

            return self::SUCCESS;
        }

        $this->info("Reading backup from: {$filePath}...");

        try {
            $content = file_get_contents($filePath);
            if (str_ends_with($filePath, '.gz')) {
                $content = gzdecode($content);
                if ($content === false) {
                    throw new \RuntimeException('Failed to decompress gzip backup file.');
                }
            }

            $connection = DB::connection();
            $connection->unprepared("SET FOREIGN_KEY_CHECKS=0;\n");
            $connection->unprepared($content);
            $connection->unprepared("SET FOREIGN_KEY_CHECKS=1;\n");

            PlatformOperationLog::create([
                'action' => 'database restored',
                'payload' => [
                    'source_file' => basename($filePath),
                    'size_bytes' => filesize($filePath),
                ],
                'occurred_at' => now(),
            ]);

            $this->info('Database successfully restored from backup.');
            $this->call('tenancy:audit');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Database restoration failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
