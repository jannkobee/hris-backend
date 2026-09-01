<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationDataExportRequest;
use App\Models\OrganizationDataExport;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Organizations\OrganizationDataExportService;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrganizationDataExportController extends Controller
{
    private OrganizationDataExportService $exports;

    private AuditLogServiceInterface $auditLogs;

    private ResponseServiceInterface $response;

    public function __construct(OrganizationDataExportService $exports, AuditLogServiceInterface $auditLogs, ResponseServiceInterface $response)
    {
        $this->exports = $exports;
        $this->auditLogs = $auditLogs;
        $this->response = $response;
        $this->middleware('permission:manage-organization-settings');
    }

    public function index()
    {
        return $this->response->successResponse('Organization data exports', OrganizationDataExport::query()->latest()->get());
    }

    public function store(StoreOrganizationDataExportRequest $request)
    {
        $this->ensureOwner($request);

        return $this->response->storeResponse('Organization data export request', $this->exports->request($request->user()));
    }

    public function download(Request $request, OrganizationDataExport $organizationDataExport)
    {
        $this->ensureOwner($request);
        abort_unless($organizationDataExport->isDownloadable(), 409, 'This organization export is not available for download.');
        $this->auditLogs->insertLog($organizationDataExport, 'download organization data export');

        return Storage::disk($organizationDataExport->disk)->download($organizationDataExport->path, 'organization-data-export-'.$organizationDataExport->created_at->format('YmdHis').'.json', ['Content-Type' => 'application/json']);
    }

    private function ensureOwner(Request $request): void
    {
        if ($request->user()?->role?->name !== 'Admin') {
            throw new AuthorizationException('Only the organization owner can export organization data.');
        }
    }
}
