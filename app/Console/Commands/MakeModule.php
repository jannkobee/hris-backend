<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModule extends Command
{
    protected $signature = 'make:module {name}';
    protected $description = 'Scaffold module structure without overwriting existing files, and auto-register routes & repository bindings (now includes Model)';

    public function handle(): int
    {
        $rawName = $this->argument('name');

        // Normalize naming (supports: "Employment Status", "employment_status", "employment-status", "EmploymentStatus")
        $className   = Str::studly($rawName);                   // EmploymentStatus
        $camelName   = Str::camel($rawName);                    // employmentStatus (route-model binding param)
        $snakeName   = Str::snake($rawName);                    // employment_status (route file folder/file)
        $pluralSnake = Str::snake(Str::pluralStudly($rawName)); // employment_statuses (table)
        $kebabPlural = Str::kebab(Str::pluralStudly($rawName)); // employment-statuses (API uri)

        // Directories
        $controllerDir = app_path("Http/Controllers/{$className}");
        $requestDir    = app_path("Http/Requests");
        $repoDir       = app_path("Repository/{$className}");
        $apiDir        = base_path("routes/api/{$snakeName}");
        $migrationDir  = database_path('migrations');

        // Files
        $controllerPath = "{$controllerDir}/{$className}Controller.php";
        $requestPath    = "{$requestDir}/{$className}Request.php";
        $repoPath       = "{$repoDir}/{$className}Repository.php";
        $repoIntPath    = "{$repoDir}/{$className}RepositoryInterface.php";
        $apiPath        = "{$apiDir}/{$snakeName}.php";

        // Model
        $modelPath      = app_path("Models/{$className}.php");

        // Create directories safely
        $this->ensureDir($controllerDir);
        $this->ensureDir($requestDir);
        $this->ensureDir($repoDir);
        $this->ensureDir($apiDir);

        // Create files safely (with contents)
        $this->ensureFile($repoIntPath, $this->repositoryInterfaceStub($className));
        $this->ensureFile($repoPath, $this->repositoryStub($className));
        $this->ensureFile($controllerPath, $this->controllerStub($className));
        $this->ensureFile($requestPath, $this->requestStub($className, $camelName, $pluralSnake));
        $this->ensureFile($apiPath, $this->apiStub($className, $kebabPlural));

        // Create model safely (with contents)
        $this->ensureFile($modelPath, $this->modelStub($className));

        // Create migration safely (with contents) - skips if a migration for this table already exists
        $this->ensureMigration($migrationDir, $pluralSnake, $className);

        // Auto-register route include and repository bindings (non-destructive)
        $this->registerApiRouteInclude($snakeName);
        $this->registerRepositoryBinding($className);

        $this->info("✅ {$className} module created (existing files untouched).");

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

    private function ensureFile(string $path, string $content): void
    {
        if (!File::exists($path)) {
            File::put($path, $content);
            $this->line("📄 Created file: {$path}");
        } else {
            $this->line("↪️  File exists, skipped: {$path}");
        }
    }

    /**
     * Creates a migration for the module's table, unless one already exists.
     * Migrations are timestamp-prefixed, so we can't rely on ensureFile()'s
     * exact-path check — instead we glob for any existing
     * "*_create_{table}_table.php" file before creating a new one.
     */
    private function ensureMigration(string $migrationDir, string $pluralSnake, string $className): void
    {
        $this->ensureDir($migrationDir);

        $pattern = "{$migrationDir}/*_create_{$pluralSnake}_table.php";
        $existing = File::glob($pattern);

        if (!empty($existing)) {
            $this->line("↪️  Migration exists, skipped: create_{$pluralSnake}_table");
            return;
        }

        $timestamp = date('Y_m_d_His');
        $migrationPath = "{$migrationDir}/{$timestamp}_create_{$pluralSnake}_table.php";

        File::put($migrationPath, $this->migrationStub($pluralSnake, $className));
        $this->line("📄 Created file: {$migrationPath}");
    }

    /**
     * Adds "'module/module'," into routes/api.php inside the authenticated $routes array (if not present).
     */
    private function registerApiRouteInclude(string $snakeName): void
    {
        $apiRoutesPath = base_path('routes/api.php');

        if (!File::exists($apiRoutesPath)) {
            $this->warn("⚠️ routes/api.php not found. Skipped auto route registration.");
            return;
        }

        $needle = "{$snakeName}/{$snakeName}";
        $contents = File::get($apiRoutesPath);

        // Already registered?
        if (preg_match("/['\"]" . preg_quote($needle, '/') . "['\"]\s*,?/", $contents)) {
            $this->line("↪️  API route already registered, skipped: {$needle}");
            return;
        }

        // Find the authenticated $routes array and insert before closing bracket
        $pattern = '/(\$routes\s*=\s*\[\s*)([\s\S]*?)(\s*\];)/m';

        if (!preg_match($pattern, $contents)) {
            $this->warn("⚠️ Could not find \$routes array in routes/api.php. Skipped auto route registration.");
            return;
        }

        // Append with same indentation style (8 spaces used in your example)
        $insertion = "        '{$needle}',\n";

        // Insert just before the closing ];
        $newContents = preg_replace(
            $pattern,
            '$1$2' . $insertion . '$3',
            $contents,
            1
        );

        if ($newContents === null) {
            $this->warn("⚠️ Failed to update routes/api.php. Skipped auto route registration.");
            return;
        }

        File::put($apiRoutesPath, $newContents);
        $this->info("✅ Registered API route include in routes/api.php: {$needle}");
    }

    /**
     * Adds use statements + bind line in app/Providers/RepositoryServiceProvider.php (if not present).
     */
    private function registerRepositoryBinding(string $className): void
    {
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');

        if (!File::exists($providerPath)) {
            $this->warn("⚠️ RepositoryServiceProvider.php not found. Skipped repository auto-binding.");
            return;
        }

        $contents = File::get($providerPath);

        $useRepo = "use App\\Repository\\{$className}\\{$className}Repository;";
        $useInt  = "use App\\Repository\\{$className}\\{$className}RepositoryInterface;";

        // Add use statements if missing (place before Illuminate\Support\ServiceProvider; if possible)
        if (!str_contains($contents, $useRepo) || !str_contains($contents, $useInt)) {
            $useInsertPoint = "use Illuminate\\Support\\ServiceProvider;\n";

            if (str_contains($contents, $useInsertPoint)) {
                $toInsert = '';
                if (!str_contains($contents, $useRepo)) $toInsert .= $useRepo . "\n";
                if (!str_contains($contents, $useInt))  $toInsert .= $useInt . "\n";

                $contents = str_replace($useInsertPoint, $toInsert . $useInsertPoint, $contents);
            } else {
                // Fallback: insert after namespace line
                $contents = preg_replace(
                    '/(namespace\s+App\\\\Providers;\s*)/m',
                    "$1\n{$useRepo}\n{$useInt}\n",
                    $contents,
                    1
                ) ?? $contents;
            }
        }

        // Add bind line if missing
        $bindLine = "\$this->app->bind({$className}RepositoryInterface::class, {$className}Repository::class);";

        if (str_contains($contents, $bindLine)) {
            $this->line("↪️  Repository binding already exists, skipped: {$className}");
            File::put($providerPath, $contents); // persist any use insertions
            return;
        }

        // Insert bind line near the end of register() method, before its closing brace
        $registerPattern = '/(public function register\(\): void\s*\{\s*)([\s\S]*?)(\s*\}\s*)/m';

        if (!preg_match($registerPattern, $contents)) {
            $this->warn("⚠️ Could not find register() method in RepositoryServiceProvider. Skipped repository auto-binding.");
            File::put($providerPath, $contents); // persist any use insertions
            return;
        }

        // Keep indentation consistent with your existing binds (8 spaces)
        $bindInsertion = "        {$bindLine}\n";

        $contents = preg_replace(
            $registerPattern,
            '$1$2' . $bindInsertion . '$3',
            $contents,
            1
        ) ?? $contents;

        File::put($providerPath, $contents);
        $this->info("✅ Registered repository binding: {$className}RepositoryInterface -> {$className}Repository");
    }

    private function repositoryInterfaceStub(string $className): string
    {
        return <<<PHP
<?php

namespace App\Repository\\{$className};

interface {$className}RepositoryInterface
{
    //
}

PHP;
    }

    private function repositoryStub(string $className): string
    {
        return <<<PHP
<?php

namespace App\Repository\\{$className};

use App\Models\\{$className};
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;

class {$className}Repository extends BaseRepository implements {$className}RepositoryInterface
{
    public function __construct({$className} \$model, ResponseServiceInterface \$responseService, AuditLogServiceInterface \$auditLogService)
    {
        parent::__construct(\$model, \$responseService, \$auditLogService);
    }
}

PHP;
    }

    private function controllerStub(string $className): string
    {
        return <<<PHP
<?php

namespace App\Http\Controllers\\{$className};

use App\Http\Controllers\Controller;
use App\Http\Requests\\{$className}Request as ModelRequest;
use App\Repository\\{$className}\\{$className}RepositoryInterface;

class {$className}Controller extends Controller
{
    private \$modelRepository;

    public function __construct({$className}RepositoryInterface \$modelRepository)
    {
        \$this->modelRepository = \$modelRepository;
    }

    public function index()
    {
        return \$this->modelRepository->getList();
    }

    public function store(ModelRequest \$request)
    {
        return \$this->modelRepository->create(\$request->validated());
    }

    public function show(string \$id)
    {
        return \$this->modelRepository->find(\$id);
    }

    public function update(ModelRequest \$request, string \$id)
    {
        return \$this->modelRepository->update(\$request->validated(), \$id);
    }

    public function destroy(string \$id)
    {
        return \$this->modelRepository->delete(\$id);
    }
}

PHP;
    }

    private function requestStub(string $className, string $camelRouteParam, string $pluralSnakeTable): string
    {
        return <<<PHP
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class {$className}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        \$model = \$this->route('{$camelRouteParam}');
        \$modelId = is_object(\$model) ? \$model->id : \$model;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('{$pluralSnakeTable}', 'name')->ignore(\$modelId),
            ],
            'description' => ['nullable', 'string'],
        ];
    }
}

PHP;
    }

    private function apiStub(string $className, string $kebabPluralUri): string
    {
        $controllerFqn = "App\\Http\\Controllers\\{$className}\\{$className}Controller";

        return <<<PHP
<?php

use {$controllerFqn};
use Illuminate\Support\Facades\Route;

Route::apiResource('/{$kebabPluralUri}', {$className}Controller::class);

PHP;
    }

    private function modelStub(string $className): string
    {
        // default fillable/filterable for simple master-data modules
        $fillable = [
            'name',
            'description',
        ];

        $filterable = [
            'name',
            'description',
        ];

        // Special case: Position usually has department_id relation
        $extraRelation = '';
        if ($className === 'Position') {
            $fillable = ['department_id', 'name', 'description'];

            $extraRelation = <<<PHP

    public function department()
    {
        return \$this->belongsTo(Department::class);
    }

PHP;
        }

        $fillableLines = implode(",\n        ", array_map(fn($f) => "'{$f}'", $fillable));
        $filterableLines = implode(",\n        ", array_map(fn($f) => "'{$f}'", $filterable));

        return <<<PHP
<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class {$className} extends Model
{
    use HasUuids, HasFilterScope;

    protected \$fillable = [
        {$fillableLines},
    ];

    protected array \$filterable = [
        {$filterableLines},
    ];
{$extraRelation}}
PHP;
    }

    private function migrationStub(string $pluralSnake, string $className): string
    {
        // Mirrors modelStub()'s column set. Position gets a department_id FK,
        // matching its extra belongsTo(Department::class) relation.
        $extraColumn = '';
        if ($className === 'Position') {
            $extraColumn = "            \$table->foreignUuid('department_id')->nullable()->constrained('departments')->nullOnDelete();\n";
        }

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$pluralSnake}', function (Blueprint \$table) {
            \$table->uuid('id')->primary();
{$extraColumn}            \$table->string('name');
            \$table->text('description')->nullable();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$pluralSnake}');
    }
};

PHP;
    }
}
