<?php

namespace App\Console\Commands;

use App\Services\Deployment\StagingEnvironmentValidator;
use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

class ValidateStagingEnvironment extends Command
{
    protected $signature = 'deployment:validate-staging {--env-file=deploy/.env.production : Deployment environment file to validate}';

    protected $description = 'Validate staging deployment settings without displaying secret values.';

    public function handle(StagingEnvironmentValidator $validator): int
    {
        $option = (string) $this->option('env-file');
        $isAbsolute = str_starts_with($option, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $option) === 1;
        $path = $isAbsolute ? $option : base_path($option);

        if (! File::isFile($path)) {
            $this->error('Staging environment file was not found.');

            return self::FAILURE;
        }

        try {
            $result = $validator->validate(Dotenv::parse(File::get($path)));
        } catch (Throwable) {
            $this->error('Staging environment file could not be parsed.');

            return self::FAILURE;
        }

        foreach ($result['warnings'] as $warning) {
            $this->warn("WARNING: {$warning}");
        }
        foreach ($result['errors'] as $error) {
            $this->error("ERROR: {$error}");
        }

        if ($result['errors'] !== []) {
            $this->error('Staging environment validation failed. No secret values were displayed.');

            return self::FAILURE;
        }

        $this->info('Staging environment validation passed. No secret values were displayed.');

        return self::SUCCESS;
    }
}
