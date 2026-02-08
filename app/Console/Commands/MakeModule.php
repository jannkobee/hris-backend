<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModule extends Command
{
    protected $signature = 'make:module {name}';
    protected $description = 'Scaffold module structure without overwriting existing files';

    public function handle(): int
    {
        $name  = Str::studly($this->argument('name'));
        $lower = Str::lower($name);

        // Directories
        $controllerDir = app_path("Http/Controllers/{$name}");
        $requestDir    = app_path("Http/Requests");
        $repoDir       = app_path("Repository/{$name}");
        $apiDir        = base_path("routes/api/{$lower}");

        // Files
        $controllerPath = "{$controllerDir}/{$name}Controller.php";
        $requestPath    = "{$requestDir}/{$name}Request.php";

        $repoPath       = "{$repoDir}/{$name}Repository.php";
        $repoIntPath    = "{$repoDir}/{$name}RepositoryInterface.php";

        $apiPath        = "{$apiDir}/{$lower}.php";

        // Create directories safely
        $this->ensureDir($controllerDir);
        $this->ensureDir($requestDir);
        $this->ensureDir($repoDir);
        $this->ensureDir($apiDir);

        // Create files safely (empty)
        $this->ensureFile($controllerPath);
        $this->ensureFile($requestPath);
        $this->ensureFile($repoPath);
        $this->ensureFile($repoIntPath);
        $this->ensureFile($apiPath);

        $this->info("✅ {$name} module created (existing files untouched).");

        return Command::SUCCESS;
    }

    private function ensureDir(string $path): void
    {
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
            $this->line("📁 Created directory: {$path}");
        } else {
            $this->line("↪️  Directory exists, skipped: {$path}");
        }
    }

    private function ensureFile(string $path): void
    {
        if (!File::exists($path)) {
            File::put($path, '');
            $this->line("📄 Created file: {$path}");
        } else {
            $this->line("↪️  File exists, skipped: {$path}");
        }
    }
}
