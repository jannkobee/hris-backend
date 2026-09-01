<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AuditEncryptionConfiguration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:encryption-audit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that every registered sensitive field uses Laravel encrypted casts.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (blank(config('app.key'))) {
            $this->error('APP_KEY is required for encrypted model casts.');

            return self::FAILURE;
        }

        $failures = 0;
        $rows = [];
        foreach (config('security.encrypted_fields', []) as $modelClass => $fields) {
            $casts = (new $modelClass)->getCasts();
            foreach ($fields as $field) {
                $cast = (string) ($casts[$field] ?? '');
                $valid = str_starts_with($cast, 'encrypted');
                $failures += $valid ? 0 : 1;
                $rows[] = [$modelClass, $field, $cast ?: '-', $valid ? 'yes' : 'no'];
            }
        }

        $this->table(['Model', 'Field', 'Cast', 'Encrypted'], $rows);

        if ($failures > 0) {
            $this->error("{$failures} sensitive field(s) are not encrypted.");

            return self::FAILURE;
        }

        $this->info('Sensitive-field encryption configuration is valid.');

        return self::SUCCESS;
    }
}
