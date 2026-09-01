<?php

namespace App\Services\Organizations;

use App\Models\Organization;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Tenancy\TenantContext;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class OrganizationBrandingService
{
    private TenantContext $tenantContext;

    private AuditLogServiceInterface $auditLogService;

    public function __construct(
        TenantContext $tenantContext,
        AuditLogServiceInterface $auditLogService
    ) {
        $this->tenantContext = $tenantContext;
        $this->auditLogService = $auditLogService;
    }

    public function current(): Organization
    {
        return $this->tenantContext->organization();
    }

    public function branding(): array
    {
        $organization = $this->current()->fresh() ?? $this->current();

        return [
            'id' => $organization->id,
            'name' => $organization->name,
            'brand_logo_url' => $organization->brand_logo_url,
        ];
    }

    public function uploadLogo(UploadedFile $logo): array
    {
        $organization = $this->current();
        $disk = 'local';
        $originalName = $this->safeOriginalName($logo);
        $path = $logo->storeAs(
            "organization-branding/{$organization->id}",
            Str::uuid().'.'.$logo->extension(),
            $disk
        );

        if (! $path) {
            abort(500, 'The organization logo could not be stored.');
        }

        $oldDisk = $organization->brand_logo_disk;
        $oldPath = $organization->brand_logo_path;

        try {
            $organization->update([
                'brand_logo_disk' => $disk,
                'brand_logo_path' => $path,
                'brand_logo_name' => $originalName,
                'brand_logo_mime' => $logo->getMimeType(),
                'brand_logo_size' => $logo->getSize(),
            ]);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }

        if ($oldDisk && $oldPath) {
            Storage::disk($oldDisk)->delete($oldPath);
        }

        $this->auditLogService->insertLog($organization, 'update organization logo', [
            'record_id' => $organization->id,
            'file_name' => $originalName,
            'file_size' => $logo->getSize(),
        ]);

        return $this->branding();
    }

    public function removeLogo(): array
    {
        $organization = $this->current();
        $logoDisk = $organization->brand_logo_disk;
        $logoPath = $organization->brand_logo_path;

        $organization->update([
            'brand_logo_disk' => null,
            'brand_logo_path' => null,
            'brand_logo_name' => null,
            'brand_logo_mime' => null,
            'brand_logo_size' => null,
        ]);

        if ($logoDisk && $logoPath) {
            Storage::disk($logoDisk)->delete($logoPath);
        }

        $this->auditLogService->insertLog($organization, 'remove organization logo', [
            'record_id' => $organization->id,
        ]);

        return $this->branding();
    }

    public function logoResponse(): StreamedResponse
    {
        $organization = $this->current();

        if (! $organization->brand_logo_disk || ! $organization->brand_logo_path) {
            abort(404, 'Organization logo not found.');
        }

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk($organization->brand_logo_disk);
        if (! $storage->exists($organization->brand_logo_path)) {
            abort(404, 'Organization logo not found.');
        }

        return $storage->response(
            $organization->brand_logo_path,
            $organization->brand_logo_name,
            ['Content-Type' => $organization->brand_logo_mime ?: 'application/octet-stream']
        );
    }

    private function safeOriginalName(UploadedFile $logo): string
    {
        $name = basename(str_replace('\\', '/', $logo->getClientOriginalName()));

        return Str::limit($name ?: 'organization-logo.'.$logo->extension(), 255, '');
    }
}
