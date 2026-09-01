<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\AppSettings\AppSettingService;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Payroll\PayrollCalculator;
use App\Services\Payroll\PayrollWorkSummaryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollController extends Controller
{
    private AppSettingService $settings;

    private PayrollCalculator $calculator;

    private PayrollWorkSummaryService $workSummaryService;

    private AuditLogServiceInterface $auditLogService;

    public function __construct(
        AppSettingService $settings,
        PayrollCalculator $calculator,
        PayrollWorkSummaryService $workSummaryService,
        AuditLogServiceInterface $auditLogService,
    ) {
        $this->settings = $settings;
        $this->calculator = $calculator;
        $this->workSummaryService = $workSummaryService;
        $this->auditLogService = $auditLogService;
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureEnabled();
        $query = PayrollPeriod::query()->withCount('items')->orderByDesc('date_from');

        if (! $this->canViewCompanyPayroll($request)) {
            $employeeId = $this->employeeId($request);
            $query->whereIn('status', ['approved', 'paid'])
                ->whereHas('items', fn (Builder $query) => $query->where('employee_id', $employeeId));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->string('search').'%');
        }

        return response()->json(['data' => $query->paginate((int) $request->input('limit', 10))]);
    }

    public function show(Request $request, PayrollPeriod $period): JsonResponse
    {
        $this->ensureEnabled();
        if ($this->canViewCompanyPayroll($request)) {
            $period->load('items.employee.user', 'creator', 'approver');
        } else {
            if (! in_array($period->status, ['approved', 'paid'], true)) {
                throw new AuthorizationException('This payslip is not available until payroll is approved.');
            }
            $employeeId = $this->employeeId($request);
            $period->load([
                'items' => fn ($query) => $query->where('employee_id', $employeeId)->with('employee.user'),
                'creator',
                'approver',
            ]);
            if ($period->items->isEmpty()) {
                throw new AuthorizationException('You cannot view this payroll period.');
            }
        }

        return response()->json(['data' => $period]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureEnabled();
        $this->authorizePermission($request, 'manage-payroll');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'payout_date' => ['required', 'date', 'after_or_equal:date_to'],
            'frequency' => ['required', 'in:monthly,semi_monthly'],
        ]);
        $this->ensureNoOverlap($data['date_from'], $data['date_to'], $data['frequency']);

        $period = PayrollPeriod::create(array_merge($data, [
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]));
        $this->auditLogService->insertLog($period, 'create', ['record_id' => $period->id, 'after' => $period->toArray()]);

        return response()->json(['message' => 'Payroll period created successfully.', 'data' => $period], 201);
    }

    public function destroy(Request $request, PayrollPeriod $period): JsonResponse
    {
        $this->ensureEnabled();
        $this->authorizePermission($request, 'manage-payroll');
        if ($period->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft payroll periods can be removed.']);
        }

        $snapshot = $period->toArray();
        $period->delete();
        $this->auditLogService->insertLog($period, 'delete', ['record_id' => $period->id, 'before' => $snapshot]);

        return response()->json(['message' => 'Payroll period removed successfully.', 'data' => true]);
    }

    public function process(Request $request, PayrollPeriod $period): JsonResponse
    {
        $this->ensureEnabled();
        $this->authorizePermission($request, 'manage-payroll');
        if ($period->isLocked()) {
            throw ValidationException::withMessages([
                'status' => 'Locked payroll periods cannot be regenerated.',
            ]);
        }
        if (! in_array($period->status, ['draft', 'processed'], true)) {
            throw ValidationException::withMessages(['status' => 'Only draft or processed payroll periods can be generated.']);
        }

        $employees = Employee::query()
            ->where('basic_monthly_salary', '>', 0)
            ->where('pay_schedule', $period->frequency)
            ->whereDoesntHave(
                'employmentStatus',
                fn (Builder $query) => $query->whereRaw('LOWER(name) = ?', ['separated'])
            )
            ->where(function (Builder $query) use ($period): void {
                $query->whereNull('hire_date')->orWhereDate('hire_date', '<=', $period->date_to);
            })
            ->get();

        if ($employees->isEmpty()) {
            throw ValidationException::withMessages([
                'employees' => 'No employees have a salary and pay schedule matching this payroll period.',
            ]);
        }

        $adjustments = $period->items()
            ->get()
            ->mapWithKeys(fn (PayrollItem $item) => [$item->employee_id => [
                'allowances' => (float) $item->allowances,
                'other_earnings' => (float) $item->other_earnings,
                'other_deductions' => (float) $item->other_deductions,
                'notes' => $item->notes,
            ]]);

        DB::transaction(function () use ($period, $employees, $adjustments): void {
            $period->items()->delete();

            foreach ($employees as $employee) {
                $overtimeHours = (float) Overtime::query()
                    ->where('employee_id', $employee->id)
                    ->where('status', 'approved')
                    ->whereBetween('date', [$period->date_from, $period->date_to])
                    ->sum('premium_hours');
                $workSummary = $this->settings->get('payroll.attendance_calculation_enabled', true)
                    ? $this->workSummaryService->summarize($employee, $period->date_from, $period->date_to)
                    : [];

                $calculation = $this->calculator->calculate(
                    $employee,
                    $period->frequency,
                    $this->calculator->overtimePay($employee, $overtimeHours),
                    $adjustments->get($employee->id, []),
                    $workSummary,
                    $period->date_to->toDateString(),
                );
                $calculation['calculation_snapshot']['approved_overtime_hours'] = $overtimeHours;
                $period->items()->create(array_merge($calculation, ['employee_id' => $employee->id]));
            }

            $period->update(array_merge($this->totals($period), [
                'status' => 'processed',
                'processed_at' => now(),
            ]));
        });

        $this->auditLogService->insertLog($period, 'process', ['record_id' => $period->id, 'employees' => $employees->count()]);

        return response()->json([
            'message' => 'Payroll processed successfully.',
            'data' => $period->fresh()->load('items.employee.user'),
        ], 202);
    }

    public function updateItem(Request $request, PayrollItem $item): JsonResponse
    {
        $this->ensureEnabled();
        $this->authorizePermission($request, 'manage-payroll');
        $item->loadMissing('period', 'employee');
        if ($item->period->isLocked()) {
            throw ValidationException::withMessages([
                'status' => 'Locked payroll items cannot be adjusted.',
            ]);
        }
        if ($item->period->status !== 'processed') {
            throw ValidationException::withMessages(['status' => 'Adjustments are only allowed before payroll approval.']);
        }

        $adjustments = array_merge([
            'allowances' => (float) $item->allowances,
            'other_earnings' => (float) $item->other_earnings,
            'other_deductions' => (float) $item->other_deductions,
            'notes' => $item->notes,
        ], $request->validate([
            'allowances' => ['nullable', 'numeric', 'min:0'],
            'other_earnings' => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]));

        $before = $item->toArray();
        $calculation = $this->calculator->calculate(
            $item->employee,
            $item->period->frequency,
            (float) $item->overtime_pay,
            $adjustments,
            $this->workSummaryFromItem($item),
            $item->period->date_to->toDateString(),
        );
        $calculation['calculation_snapshot']['approved_overtime_hours'] =
            $item->calculation_snapshot['approved_overtime_hours'] ?? null;
        $item->update($calculation);
        $item->period->update($this->totals($item->period));
        $this->auditLogService->insertLog($item, 'adjust', [
            'record_id' => $item->id,
            'before' => $before,
            'after' => $item->fresh()->toArray(),
        ]);

        return response()->json([
            'message' => 'Payslip adjustments saved successfully.',
            'data' => $item->fresh()->load('employee.user'),
        ], 202);
    }

    public function acknowledgeExceptions(Request $request, PayrollItem $item): JsonResponse
    {
        $this->ensureEnabled();
        $this->authorizePermission($request, 'manage-payroll');
        $item->loadMissing('period');
        if ($item->period->status !== 'processed') {
            throw ValidationException::withMessages(['status' => 'Exceptions can only be acknowledged before payroll approval.']);
        }
        if (empty($item->exceptions)) {
            throw ValidationException::withMessages(['exceptions' => 'This payslip has no exceptions to acknowledge.']);
        }

        $item->update([
            'exceptions_acknowledged_at' => now(),
            'exceptions_acknowledged_by' => $request->user()->id,
        ]);
        $this->auditLogService->insertLog($item, 'acknowledge payroll exceptions', [
            'record_id' => $item->id,
            'exceptions' => $item->exceptions,
        ]);

        return response()->json([
            'message' => 'Payroll exceptions acknowledged successfully.',
            'data' => $item->fresh(),
        ], 202);
    }

    public function acknowledgeAllExceptions(Request $request, PayrollPeriod $period): JsonResponse
    {
        $this->ensureEnabled();
        $this->authorizePermission($request, 'manage-payroll');
        if ($period->status !== 'processed') {
            throw ValidationException::withMessages(['status' => 'Exceptions can only be acknowledged before payroll approval.']);
        }

        $items = $period->items()->get()->filter(fn (PayrollItem $item) => ! empty($item->exceptions));
        foreach ($items as $item) {
            $item->update([
                'exceptions_acknowledged_at' => now(),
                'exceptions_acknowledged_by' => $request->user()->id,
            ]);
        }
        $this->auditLogService->insertLog($period, 'acknowledge all payroll exceptions', [
            'record_id' => $period->id,
            'items' => $items->count(),
        ]);

        return response()->json([
            'message' => 'All payroll exceptions acknowledged successfully.',
            'data' => ['acknowledged' => $items->count()],
        ], 202);
    }

    public function approve(Request $request, PayrollPeriod $period): JsonResponse
    {
        $this->ensureEnabled();
        $this->authorizePermission($request, 'approve-payroll');
        $this->ensureMakerChecker($request, $period, 'approve');
        if ($period->status !== 'processed') {
            throw ValidationException::withMessages(['status' => 'Only processed payroll can be approved.']);
        }
        $unreviewedExceptions = $period->items()->get()->filter(
            fn (PayrollItem $item) => ! empty($item->exceptions) && ! $item->exceptions_acknowledged_at
        );
        if ($unreviewedExceptions->isNotEmpty()) {
            throw ValidationException::withMessages([
                'exceptions' => "Review or acknowledge payroll exceptions for {$unreviewedExceptions->count()} employee(s) before approval.",
            ]);
        }

        $period->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
        $this->auditLogService->insertLog($period, 'approve', ['record_id' => $period->id]);

        return response()->json(['message' => 'Payroll approved successfully.', 'data' => $period->fresh()], 202);
    }

    public function lock(Request $request, PayrollPeriod $period): JsonResponse
    {
        $this->ensureEnabled();
        $this->authorizePermission($request, 'approve-payroll');
        $this->ensureMakerChecker($request, $period, 'lock');
        if ($period->status !== 'approved' || $period->isLocked()) {
            throw ValidationException::withMessages(['status' => 'Only an unlocked approved payroll can be locked.']);
        }
        DB::transaction(function () use ($period, $request): void {
            $period->items()->each(function (PayrollItem $item): void {
                $snapshot = $item->toArray();
                unset($snapshot['locked_snapshot'], $snapshot['created_at'], $snapshot['updated_at']);

                $item->update(['locked_snapshot' => $snapshot]);
            });

            $period->update(['locked_at' => now(), 'locked_by' => $request->user()->id]);
        });
        $this->auditLogService->insertLog($period, 'lock', ['record_id' => $period->id]);

        return response()->json(['message' => 'Payroll locked successfully.', 'data' => $period->fresh()], 202);
    }

    public function markPaid(Request $request, PayrollPeriod $period): JsonResponse
    {
        $this->ensureEnabled();
        $this->authorizePermission($request, 'mark-payroll-paid');
        $this->ensureMakerChecker($request, $period, 'mark paid');
        if ($period->status !== 'approved' || ! $period->isLocked()) {
            throw ValidationException::withMessages(['status' => 'Only locked approved payroll can be marked as paid.']);
        }

        $period->update(['status' => 'paid', 'paid_at' => now()]);
        $this->auditLogService->insertLog($period, 'mark paid', ['record_id' => $period->id]);

        return response()->json(['message' => 'Payroll marked as paid successfully.', 'data' => $period->fresh()], 202);
    }

    public function exportCsv(Request $request, PayrollPeriod $period): StreamedResponse
    {
        $this->ensureEnabled();
        $this->authorizePermission($request, 'view-payroll');
        $period->load('items.employee.user');
        $filename = (string) str($period->name)->slug()->append('-payroll-register.csv');
        $this->auditLogService->insertLog($period, 'export payroll register', [
            'record_id' => $period->id,
            'format' => 'csv',
            'employees' => $period->items->count(),
        ]);

        return response()->streamDownload(function () use ($period): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Employee No.', 'Employee', 'Scheduled Days', 'Days Worked', 'Paid Leave', 'Unpaid Leave',
                'Absent Days', 'Late Minutes', 'Undertime Minutes', 'Basic Pay', 'Overtime Pay', 'Allowances',
                'Other Earnings', 'Gross Pay', 'Absence Deduction', 'Late/Undertime Deduction',
                'Unpaid Leave Deduction', 'SSS', 'PhilHealth', 'Pag-IBIG', 'Withholding Tax',
                'Other Deductions', 'Total Deductions', 'Net Pay', 'Exceptions', 'Status',
            ]);

            foreach ($period->items as $item) {
                fputcsv($handle, [
                    $this->csvValue($item->employee?->employee_no),
                    $this->csvValue($item->employee?->user?->full_name),
                    $item->scheduled_days,
                    $item->days_worked,
                    $item->paid_leave_days,
                    $item->unpaid_leave_days,
                    $item->absent_days,
                    $item->late_minutes,
                    $item->undertime_minutes,
                    $item->basic_pay,
                    $item->overtime_pay,
                    $item->allowances,
                    $item->other_earnings,
                    $item->gross_pay,
                    $item->absence_deduction,
                    $item->late_undertime_deduction,
                    $item->unpaid_leave_deduction,
                    $item->sss_employee,
                    $item->philhealth_employee,
                    $item->pagibig_employee,
                    $item->withholding_tax,
                    $item->other_deductions,
                    $item->total_deductions,
                    $item->net_pay,
                    $this->csvValue(collect($item->exceptions)->pluck('message')->join('; ')),
                    $period->status,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function totals(PayrollPeriod $period): array
    {
        return [
            'total_gross' => round((float) $period->items()->sum('gross_pay'), 2),
            'total_deductions' => round((float) $period->items()->sum('total_deductions'), 2),
            'total_net' => round((float) $period->items()->sum('net_pay'), 2),
        ];
    }

    private function workSummaryFromItem(PayrollItem $item): array
    {
        return [
            'scheduled_days' => (float) $item->scheduled_days,
            'days_worked' => (float) $item->days_worked,
            'paid_leave_days' => (float) $item->paid_leave_days,
            'unpaid_leave_days' => (float) $item->unpaid_leave_days,
            'absent_days' => (float) $item->absent_days,
            'late_minutes' => $item->late_minutes,
            'undertime_minutes' => $item->undertime_minutes,
            'exceptions' => $item->exceptions ?? [],
            'attendance_records' => $item->calculation_snapshot['attendance_records'] ?? 0,
            'approved_leave_requests' => $item->calculation_snapshot['approved_leave_requests'] ?? 0,
        ];
    }

    private function csvValue(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@]/', $value) ? "'{$value}" : $value;
    }

    private function ensureNoOverlap(string $from, string $to, string $frequency): void
    {
        if (PayrollPeriod::query()
            ->where('frequency', $frequency)
            ->where('date_from', '<=', $to)
            ->where('date_to', '>=', $from)
            ->exists()) {
            throw ValidationException::withMessages(['date_from' => 'This range overlaps an existing payroll period.']);
        }
    }

    private function ensureMakerChecker(Request $request, PayrollPeriod $period, string $action): void
    {
        $actorId = (string) $request->user()?->getKey();
        $makerIds = array_filter([(string) $period->created_by]);

        if (in_array($actorId, $makerIds, true)) {
            throw new AuthorizationException("The payroll maker cannot {$action} the same payroll period.");
        }

        if ($action === 'mark paid' && (string) $period->approved_by === $actorId) {
            throw new AuthorizationException('The payroll approver cannot mark the same payroll period as paid.');
        }
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user?->hasPermission($permission)) {
            throw new AuthorizationException('You do not have permission to perform this payroll action.');
        }
    }

    private function canViewCompanyPayroll(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();

        return (bool) $user?->hasPermission('view-payroll');
    }

    private function employeeId(Request $request): string
    {
        $employeeId = $request->user()?->employee?->id;
        if (! $employeeId) {
            throw new AuthorizationException('This account is not linked to an employee profile.');
        }

        return $employeeId;
    }

    private function ensureEnabled(): void
    {
        if (! $this->settings->get('payroll.enabled', true)) {
            throw new AuthorizationException('Payroll is disabled in App Settings.');
        }
    }
}
