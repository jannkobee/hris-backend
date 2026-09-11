<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReimburseExpenseClaimRequest;
use App\Http\Requests\ReviewExpenseClaimRequest;
use App\Http\Requests\StoreExpenseClaimRequest;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Services\AuditLog\AuditLogServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ExpenseClaimController extends Controller
{
    private AuditLogServiceInterface $auditLogs;

    public function __construct(AuditLogServiceInterface $auditLogs)
    {
        $this->auditLogs = $auditLogs;
        $this->middleware('permission:manage-employees')->only('review');
        $this->middleware('permission:manage-payroll')->only('reimburse');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = ExpenseClaim::query()->with('employee.user')->latest();
        $query = ExpenseClaim::query()->with(['employee.user', 'employee.department'])->latest();
        if (! $user->hasAnyPermission(['view-employees', 'manage-employees', 'manage-payroll'])) {
            // Ordinary employees can track their own claims, never a colleague's.
            $query->whereHas('employee', fn ($employee) => $employee->where('user_id', $user->getKey()));
            $query->whereHas('employee', fn($employee) => $employee->where('user_id', $user->getKey()));
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(StoreExpenseClaimRequest $request)
    {
        $employee = Employee::query()->findOrFail($request->validated('employee_id'));
        abort_unless($employee->user_id === $request->user()->id, 403);
        $claim = ExpenseClaim::query()->create($request->validated() + ['status' => 'submitted']);

        $data = $request->safe()->except(['receipt']);

        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            $disk = config('filesystems.default');
            $orgId = $employee->organization_id;
            $path = $file->store("organizations/{$orgId}/expense-receipts", ['disk' => $disk]);
            $data['receipt_path'] = $path;
        }

        $claim = ExpenseClaim::query()->create($data + ['status' => 'submitted']);
        $this->auditLogs->insertLog($claim, 'submit expense claim');

        return response()->json(['message' => 'Expense claim submitted successfully.', 'data' => $claim], 201);
        return response()->json([
            'message' => 'Expense claim submitted successfully.',
            'data' => $claim->fresh()->load('employee.user'),
        ], 201);
    }

    public function receipt(Request $request, ExpenseClaim $claim)
    {
        $user = $request->user();
        $isOwner = $claim->employee && $claim->employee->user_id === $user->getKey();
        $hasPermission = $user->hasAnyPermission(['view-employees', 'manage-employees', 'manage-payroll']);
        abort_unless($isOwner || $hasPermission, 403, 'You are not authorized to view this receipt.');
        abort_unless($claim->receipt_path, 404, 'No receipt attached to this claim.');

        $disk = config('filesystems.default');
        $storage = Storage::disk($disk);
        abort_unless($storage->exists($claim->receipt_path), 404, 'Receipt file not found.');

        $mime = $storage->mimeType($claim->receipt_path) ?: 'application/octet-stream';
        $filename = 'receipt-' . $claim->id . '.' . pathinfo($claim->receipt_path, PATHINFO_EXTENSION);

        return response($storage->get($claim->receipt_path), 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function export(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasAnyPermission(['view-employees', 'manage-employees', 'manage-payroll']), 403, 'Unauthorized.');

        $query = ExpenseClaim::query()->with(['employee.user', 'employee.department'])->latest();
        if ($status = $request->query('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }
        if ($from = $request->query('from')) {
            $query->whereDate('expense_date', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('expense_date', '<=', $to);
        }

        $claims = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="expense-claims-export-' . now()->format('YmdHis') . '.csv"',
        ];

        $callback = function () use ($claims) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Claim ID',
                'Employee Number',
                'Employee Name',
                'Department',
                'Expense Date',
                'Category',
                'Description',
                'Amount (PHP)',
                'Status',
                'Has Receipt',
                'Reviewed By',
                'Reviewed At',
                'Reviewer Note',
                'Payment Reference',
                'Reimbursed At',
            ]);

            foreach ($claims as $claim) {
                fputcsv($handle, [
                    $claim->id,
                    $claim->employee?->employee_no ?? '',
                    $claim->employee?->user?->first_name ? trim(($claim->employee->user->first_name ?? '') . ' ' . ($claim->employee->user->last_name ?? '')) : '',
                    $claim->employee?->department?->name ?? '',
                    $claim->expense_date ? $claim->expense_date->toDateString() : '',
                    $claim->category,
                    $claim->description,
                    number_format((float) $claim->amount, 2, '.', ''),
                    ucfirst($claim->status),
                    $claim->has_receipt ? 'Yes' : 'No',
                    $claim->reviewed_by ?? '',
                    $claim->reviewed_at ? $claim->reviewed_at->toIso8601String() : '',
                    $claim->reviewer_note ?? '',
                    $claim->payment_reference ?? '',
                    $claim->reimbursed_at ? $claim->reimbursed_at->toIso8601String() : '',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function review(ReviewExpenseClaimRequest $request, ExpenseClaim $claim)
    {
        if ($claim->status !== 'submitted') {
            throw ValidationException::withMessages(['status' => 'Only submitted claims can be reviewed.']);
        }
        $claim->update($request->validated() + ['reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        $this->auditLogs->insertLog($claim, 'review expense claim');

        return response()->json(['message' => 'Expense claim reviewed successfully.', 'data' => $claim->fresh()]);
        return response()->json(['message' => 'Expense claim reviewed successfully.', 'data' => $claim->fresh()->load('employee.user')]);
    }

    public function reimburse(ReimburseExpenseClaimRequest $request, ExpenseClaim $claim)
    {
        if ($claim->status !== 'approved') {
            throw ValidationException::withMessages(['status' => 'Only approved claims can be reimbursed.']);
        }
        $claim->update($request->validated() + ['status' => 'reimbursed', 'reimbursed_by' => $request->user()->id, 'reimbursed_at' => $request->validated('reimbursed_at', now())]);
        $this->auditLogs->insertLog($claim, 'reimburse expense claim');

        return response()->json(['message' => 'Expense claim reimbursed successfully.', 'data' => $claim->fresh()]);
        return response()->json(['message' => 'Expense claim reimbursed successfully.', 'data' => $claim->fresh()->load('employee.user')]);
    }
}
