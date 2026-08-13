<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Services\AppSettings\AppSettingService;
use App\Services\AuditLog\AuditLogServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeDocumentController extends Controller
{
    public function __construct(
        private readonly AppSettingService $settings,
        private readonly AuditLogServiceInterface $auditLogService,
    ) {
    }

    public function categories(): JsonResponse
    {
        return response()->json(['data' => EmployeeDocument::CATEGORIES]);
    }

    public function index(Request $request, Employee $employee): JsonResponse
    {
        $this->ensureEnabled();
        $this->authorizeView($request, $employee);

        $documents = $employee->documents()
            ->when(
                ! $request->user()?->hasPermission('view-employee-documents'),
                fn ($query) => $query->where('visibility', 'employee')
            )
            ->with('uploader:id,first_name,middle_name,last_name')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $documents]);
    }

    public function store(Request $request, Employee $employee): JsonResponse
    {
        $this->ensureEnabled();
        $this->authorizeManage($request);

        $maxKilobytes = (int) $this->settings->get('employee_documents.max_size_mb', 10) * 1024;
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(EmployeeDocument::CATEGORIES))],
            'visibility' => ['required', Rule::in(['employee', 'hr_only'])],
            'title' => ['required', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx', "max:{$maxKilobytes}"],
        ]);

        $file = $request->file('file');
        $disk = 'local';
        $path = $file->storeAs(
            "employee-201/{$employee->id}",
            Str::uuid().'.'.$file->extension(),
            $disk
        );

        if (! $path) {
            abort(500, 'The personnel document could not be stored.');
        }

        try {
            $document = $employee->documents()->create(array_merge(
                collect($data)->except('file')->all(),
                [
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => $request->user()->id,
                ]
            ));
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }

        $this->auditLogService->insertLog($document, 'upload', [
            'record_id' => $document->id,
            'employee_id' => $employee->id,
            'file' => $file,
        ]);

        return response()->json([
            'message' => 'Employee 201 document uploaded successfully.',
            'data' => $document->load('uploader:id,first_name,middle_name,last_name'),
        ], 201);
    }

    public function update(Request $request, EmployeeDocument $document): JsonResponse
    {
        $this->ensureEnabled();
        $this->authorizeManage($request);
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(EmployeeDocument::CATEGORIES))],
            'visibility' => ['required', Rule::in(['employee', 'hr_only'])],
            'title' => ['required', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $before = $document->toArray();
        $document->update($data);
        $this->auditLogService->insertLog($document, 'update', [
            'record_id' => $document->id,
            'before' => $before,
            'after' => $document->fresh()->toArray(),
        ]);

        return response()->json([
            'message' => 'Employee 201 document updated successfully.',
            'data' => $document->fresh()->load('uploader:id,first_name,middle_name,last_name'),
        ], 202);
    }

    public function destroy(Request $request, EmployeeDocument $document): JsonResponse
    {
        $this->ensureEnabled();
        $this->authorizeManage($request);
        $snapshot = $document->toArray();

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
        $this->auditLogService->insertLog($document, 'delete', [
            'record_id' => $document->id,
            'before' => $snapshot,
        ]);

        return response()->json(['message' => 'Employee 201 document removed successfully.', 'data' => true]);
    }

    public function download(Request $request, EmployeeDocument $document)
    {
        $this->ensureEnabled();
        $this->authorizeView($request, $document->employee);
        if ($document->visibility === 'hr_only' && ! $request->user()?->hasPermission('view-employee-documents')) {
            throw new AuthorizationException('This personnel document is restricted to HR.');
        }

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk($document->disk);
        if (! $storage->exists($document->path)) {
            abort(404, 'Personnel document file not found.');
        }

        return $storage->download($document->path, $document->original_name);
    }

    private function authorizeView(Request $request, Employee $employee): void
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($employee->user_id !== $user?->id && ! $user?->hasPermission('view-employee-documents')) {
            throw new AuthorizationException('You cannot view this employee’s 201 files.');
        }
    }

    private function authorizeManage(Request $request): void
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user?->hasPermission('manage-employee-documents')) {
            throw new AuthorizationException('You do not have permission to manage employee 201 files.');
        }
    }

    private function ensureEnabled(): void
    {
        if (! $this->settings->get('employee_documents.enabled', true)) {
            throw new AuthorizationException('Employee 201 files are disabled in App Settings.');
        }
    }
}
