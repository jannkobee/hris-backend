<?php

namespace App\Repository\LeaveRequest;

use App\Mail\LeaveRequestSubmitted;
use App\Models\Employee;
use App\Models\LeaveBlackoutDate;
use App\Models\LeaveCredit;
use App\Models\LeaveCreditSetting;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestAttachment;
use App\Models\User;
use App\Repository\Base\BaseRepository;
use App\Services\Approvals\DelegatedApproverResolver;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Leave\LeaveDurationCalculator;
use App\Services\Utils\ResponseServiceInterface;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LeaveRequestRepository extends BaseRepository implements LeaveRequestRepositoryInterface
{
    private LeaveDurationCalculator $durationCalculator;

    private DelegatedApproverResolver $delegates;

    public function __construct(
        LeaveRequest $model,
        ResponseServiceInterface $responseService,
        AuditLogServiceInterface $auditLogService,
        LeaveDurationCalculator $durationCalculator,
        DelegatedApproverResolver $delegates,
    ) {
        parent::__construct($model, $responseService, $auditLogService);
        $this->durationCalculator = $durationCalculator;
        $this->delegates = $delegates;
    }

    public function getList(): JsonResponse
    {
        return parent::getList();
    }

    protected function applyVisibilityScope(Builder $query): Builder
    {
        return $this->canManageAll()
            ? $query
            : $query->where('employee_id', $this->currentEmployeeId());
    }

    public function create(array $attributes): JsonResponse
    {
        $attachments = $attributes['attachments'] ?? [];
        unset($attributes['attachments']);
        $attributes = $this->normalizeDateTimes($attributes);

        $this->ensureCanActForEmployee($attributes['employee_id']);
        $this->ensureEmployeeIsEligible($attributes['employee_id']);
        $this->ensureNotBlackout($attributes);

        $created = DB::transaction(function () use ($attributes, $attachments) {
            $this->reserveCredits($attributes);

            $request = $this->model->create([
                ...$attributes,
                'status' => 'pending',
            ]);

            $this->storeAttachments($request, $attachments);

            return $request->fresh('attachments');
        });

        $this->auditLogService->insertLog($this->model, 'create', $created->getAttributes());
        $this->notifyAdmins($created);

        return $this->responseService->storeResponse($this->model->model_name, $created);
    }

    public function find(string $id): JsonResponse
    {
        $request = $this->findRequest($id);
        $this->ensureCanActForEmployee($request->employee_id);

        return parent::find($id);
    }

    public function update(array $attributes, string|int $id): JsonResponse
    {
        $request = $this->findRequest($id);
        $this->ensureCanActForEmployee($request->employee_id);

        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending leave requests can be edited.',
            ]);
        }

        // Dates/type affect reserved balances. Changing them requires cancelling
        // and submitting a new request, keeping the balance ledger auditable.
        if (array_intersect(array_keys($attributes), ['employee_id', 'leave_type_id', 'start_at', 'end_at'])) {
            throw ValidationException::withMessages([
                'leave_request' => 'Cancel and resubmit a request to change employee, leave type, or dates.',
            ]);
        }

        return parent::update($attributes, $id);
    }

    public function delete(string $id): JsonResponse
    {
        throw ValidationException::withMessages([
            'leave_request' => 'Leave requests cannot be deleted. Cancel the request instead.',
        ]);
    }

    public function approve(string $id): JsonResponse
    {
        $this->ensureCanApprove();

        return $this->transition($id, 'approved');
    }

    public function reject(string $id, string $remarks = null): JsonResponse
    {
        $this->ensureCanApprove();

        return $this->transition($id, 'rejected', $remarks, true);
    }

    public function cancel(string $id, string $remarks = null): JsonResponse
    {
        $request = $this->findRequest($id);
        $this->ensureCanActForEmployee($request->employee_id);

        return $this->transition($id, 'cancelled', $remarks, true);
    }

    public function downloadAttachment(string $id, string $attachment)
    {
        $request = $this->findRequest($id);
        $this->ensureCanActForEmployee($request->employee_id);

        $file = $request->attachments()->find($attachment);
        if (! $file) {
            throw ValidationException::withMessages(['attachment' => 'Attachment not found.']);
        }

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk($file->disk);

        if (! $storage->exists($file->path)) {
            throw ValidationException::withMessages(['attachment' => 'Attachment file is unavailable.']);
        }

        return $storage->download($file->path, $file->original_name);
    }

    private function transition(string $id, string $status, string $remarks = null, bool $releaseCredits = false): JsonResponse
    {
        $request = DB::transaction(function () use ($id, $status, $remarks, $releaseCredits) {
            $request = $this->model->newQuery()->lockForUpdate()->find($id);

            if (! $request) {
                throw ValidationException::withMessages(['record_not_found' => 'Record not found.']);
            }

            if ($request->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Only pending leave requests can be actioned.']);
            }

            if ($releaseCredits) {
                $this->releaseCredits($request);
            }

            $request->update([
                'status' => $status,
                'approved_by' => $status === 'approved' ? Auth::id() : null,
                'approved_at' => $status === 'approved' ? now() : null,
                'remarks' => $remarks,
            ]);

            return $request->fresh();
        });

        $this->auditLogService->insertLog($this->model, $status, ['id' => $id, 'remarks' => $remarks]);

        return $this->responseService->updateResponse($this->model->model_name, $request);
    }

    private function reserveCredits(array $attributes): void
    {
        $credits = [];
        foreach ($this->durationCalculator->daysByYear($attributes['start_date'], $attributes['end_date']) as $year => $days) {
            $credit = LeaveCredit::query()->where([
                'employee_id' => $attributes['employee_id'],
                'leave_type_id' => $attributes['leave_type_id'],
                'year' => $year,
            ])->lockForUpdate()->first();

            $policy = LeaveCreditSetting::query()->where('leave_type_id', $attributes['leave_type_id'])->where('is_active', true)->first();
            $negativeLimit = $policy?->allow_negative_balance ? (float) $policy->negative_balance_limit : 0;
            if (! $credit || $credit->remaining - $days < -$negativeLimit) {
                $available = $credit?->remaining ?? 0;
                throw ValidationException::withMessages([
                    'leave_type_id' => "Insufficient leave balance for {$year}. Available: {$available} day(s).",
                ]);
            }
            $credits[] = [$credit, $days];
        }

        foreach ($credits as [$credit, $days]) {
            $credit->increment('used', $days);
        }
    }

    private function ensureNotBlackout(array $attributes): void
    {
        $blocked = LeaveBlackoutDate::query()->whereDate('start_date', '<=', $attributes['end_date'])->whereDate('end_date', '>=', $attributes['start_date'])->where(fn ($query) => $query->whereNull('leave_type_id')->orWhere('leave_type_id', $attributes['leave_type_id']))->first();
        if ($blocked) {
            throw ValidationException::withMessages(['start_at' => "Leave cannot be requested during blackout period: {$blocked->name}."]);
        }
    }

    private function releaseCredits(LeaveRequest $request): void
    {
        foreach ($this->durationCalculator->daysByYear($request->start_date, $request->end_date) as $year => $days) {
            $credit = LeaveCredit::query()->where([
                'employee_id' => $request->employee_id,
                'leave_type_id' => $request->leave_type_id,
                'year' => $year,
            ])->lockForUpdate()->first();

            if (! $credit || $credit->used < $days) {
                throw ValidationException::withMessages([
                    'leave_request' => 'Leave balance cannot be released because its reservation is inconsistent.',
                ]);
            }
            $credit->decrement('used', $days);
        }
    }

    private function ensureEmployeeIsEligible(string $employeeId): void
    {
        $employee = Employee::with('employmentStatus')->findOrFail($employeeId);
        $status = strtolower((string) $employee->employmentStatus?->name);

        if (! $employee->hire_date || $status === 'separated') {
            throw ValidationException::withMessages([
                'employee_id' => 'Only active employees with a hire date can submit leave requests.',
            ]);
        }
    }

    private function ensureCanActForEmployee(string $employeeId): void
    {
        if (! $this->canManageAll() && $this->currentEmployeeId() !== $employeeId) {
            throw new AuthorizationException('You can only manage your own leave requests.');
        }
    }

    private function ensureCanApprove(): void
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $this->delegates->canApprove($user, 'approve-leave-requests')) {
            throw new AuthorizationException('You do not have permission to approve or reject leave requests.');
        }
    }

    private function canManageAll(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) ($user?->hasPermission('manage-leave-requests')
            || $user?->hasPermission('approve-leave-requests'));
    }

    private function currentEmployeeId(): string
    {
        /** @var User|null $user */
        $user = Auth::user();
        $employeeId = $user?->employee?->id;
        if (! $employeeId) {
            throw new AuthorizationException('This account is not linked to an employee record.');
        }

        return $employeeId;
    }

    private function findRequest(string $id): LeaveRequest
    {
        $request = $this->model->find($id);
        if (! $request) {
            throw ValidationException::withMessages(['record_not_found' => 'Record not found.']);
        }

        return $request;
    }

    private function notifyAdmins(LeaveRequest $leaveRequest): void
    {
        $admins = User::whereHas('role', fn ($query) => $query->where('name', 'Admin'))->get();
        if ($admins->isNotEmpty()) {
            Mail::to($admins)->queue((new LeaveRequestSubmitted($leaveRequest))->onQueue('mail'));
        }
    }

    private function normalizeDateTimes(array $attributes): array
    {
        $start = Carbon::parse($attributes['start_at']);
        $end = Carbon::parse($attributes['end_at']);

        $attributes['start_date'] = $start->toDateString();
        $attributes['start_time'] = $start->format('H:i:s');
        $attributes['end_date'] = $end->toDateString();
        $attributes['end_time'] = $end->format('H:i:s');
        unset($attributes['start_at'], $attributes['end_at']);

        return $attributes;
    }

    /** @param  UploadedFile[]  $attachments */
    private function storeAttachments(LeaveRequest $request, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $path = $attachment->store("leave-requests/{$request->id}", 'local');

            LeaveRequestAttachment::create([
                'leave_request_id' => $request->id,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $attachment->getClientOriginalName(),
                'mime_type' => $attachment->getMimeType() ?? 'application/octet-stream',
                'size' => $attachment->getSize() ?? 0,
            ]);
        }
    }
}
