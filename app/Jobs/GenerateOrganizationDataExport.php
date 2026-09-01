<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\OrganizationDataExport;
use App\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GenerateOrganizationDataExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $exportId;

    public string $organizationId;

    public function __construct(OrganizationDataExport $export)
    {
        $this->exportId = (string) $export->getKey();
        $this->organizationId = (string) $export->organization_id;
    }

    public function handle(TenantContext $tenantContext): void
    {
        $organization = Organization::query()->find($this->organizationId);
        if (! $organization) {
            return;
        }
        $tenantContext->run($organization, function () use ($organization): void {
            $export = OrganizationDataExport::query()->find($this->exportId);
            if (! $export || $export->status !== OrganizationDataExport::STATUS_PENDING) {
                return;
            }
            $export->update(['status' => OrganizationDataExport::STATUS_PROCESSING]);
            try {
                $contents = json_encode(['format_version' => 1, 'generated_at' => now()->toIso8601String(), 'organization' => $organization->only(['id', 'slug', 'name', 'timezone', 'country_code', 'created_at']), 'data' => $this->data($organization->getKey())], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                $disk = config('data_exports.disk', 'local');
                $path = 'organization-exports/'.$organization->getKey().'/'.$export->getKey().'.json';
                Storage::disk($disk)->put($path, $contents, ['visibility' => 'private']);
                $export->update(['status' => OrganizationDataExport::STATUS_READY, 'disk' => $disk, 'path' => $path, 'checksum' => hash('sha256', $contents), 'expires_at' => now()->addHours((int) config('data_exports.retention_hours', 72))]);
            } catch (\Throwable $exception) {
                report($exception);
                $export->update(['status' => OrganizationDataExport::STATUS_FAILED, 'error_message' => 'The export could not be generated.']);
            }
        });
    }

    private function data(string $organizationId): array
    {
        return collect(config('data_exports.tables', []))->mapWithKeys(function (string $table) use ($organizationId): array {
            $rows = DB::table($table)->where('organization_id', $organizationId)->get()->map(fn (object $row): array => $this->sanitize($table, (array) $row))->all();

            return [$table => $rows];
        })->all();
    }

    private function sanitize(string $table, array $row): array
    {
        foreach (config('data_exports.excluded_columns.'.$table, config('data_exports.excluded_columns.default', [])) as $column) {
            unset($row[$column]);
        }

        return $row;
    }
}
