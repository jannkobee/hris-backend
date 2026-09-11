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
        if (! $user->hasAnyPermission(['view-employees', 'manage-employees', 'manage-payroll'])) {
            // Ordinary employees can track their own claims, never a colleague's.
            $query->whereHas('employee', fn ($employee) => $employee->where('user_id', $user->getKey()));
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(StoreExpenseClaimRequest $request)
    {
        $employee = Employee::query()->findOrFail($request->validated('employee_id'));
        abort_unless($employee->user_id === $request->user()->id, 403);
        $claim = ExpenseClaim::query()->create($request->validated() + ['status' => 'submitted']);
        $this->auditLogs->insertLog($claim, 'submit expense claim');

        return response()->json(['message' => 'Expense claim submitted successfully.', 'data' => $claim], 201);
    }

    public function review(ReviewExpenseClaimRequest $request, ExpenseClaim $claim)
    {
        if ($claim->status !== 'submitted') {
            throw ValidationException::withMessages(['status' => 'Only submitted claims can be reviewed.']);
        }
        $claim->update($request->validated() + ['reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        $this->auditLogs->insertLog($claim, 'review expense claim');

        return response()->json(['message' => 'Expense claim reviewed successfully.', 'data' => $claim->fresh()]);
    }

    public function reimburse(ReimburseExpenseClaimRequest $request, ExpenseClaim $claim)
    {
        if ($claim->status !== 'approved') {
            throw ValidationException::withMessages(['status' => 'Only approved claims can be reimbursed.']);
        }
        $claim->update($request->validated() + ['status' => 'reimbursed', 'reimbursed_by' => $request->user()->id, 'reimbursed_at' => $request->validated('reimbursed_at', now())]);
        $this->auditLogs->insertLog($claim, 'reimburse expense claim');

        return response()->json(['message' => 'Expense claim reimbursed successfully.', 'data' => $claim->fresh()]);
    }
}
