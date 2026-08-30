<?php

namespace App\Console\Commands;

use App\Traits\BelongsToOrganization;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;

class AuditTenantSchema extends Command
{
    protected $signature = 'tenancy:audit';

    protected $description = 'Verify organization ownership columns and tenant-scoped Eloquent models';

    public function handle(): int
    {
        $expectedTables = collect(config('tenancy.owned_tables', []))->unique()->sort()->values();
        $failures = collect();

        foreach ($expectedTables as $table) {
            if (! Schema::hasTable($table)) {
                $failures->push([$table, 'missing table']);

                continue;
            }

            if (! Schema::hasColumn($table, 'organization_id')) {
                $failures->push([$table, 'missing organization_id']);
            }
        }

        foreach ($this->tenantModelTables() as $model => $table) {
            if (! $expectedTables->contains($table)) {
                $failures->push([$table, "tenant model {$model} is absent from tenancy.owned_tables"]);
            }
        }

        if ($failures->isNotEmpty()) {
            $this->error('Tenant schema audit failed.');
            $this->table(['Table', 'Problem'], $failures->all());

            return self::FAILURE;
        }

        $this->info("Tenant schema audit passed for {$expectedTables->count()} tables.");

        return self::SUCCESS;
    }

    /** @return array<class-string<Model>, string> */
    private function tenantModelTables(): array
    {
        $models = [];

        foreach (File::allFiles(app_path('Models')) as $file) {
            $relative = str_replace([app_path().DIRECTORY_SEPARATOR, '.php'], '', $file->getPathname());
            $class = 'App\\Models\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

            if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract() || ! in_array(BelongsToOrganization::class, class_uses_recursive($class), true)) {
                continue;
            }

            /** @var Model $model */
            $model = $reflection->newInstanceWithoutConstructor();
            $models[$class] = $model->getTable();
        }

        return $models;
    }
}
