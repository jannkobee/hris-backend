<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStatutoryReportRunRequest;
use App\Models\PayrollItem;
use App\Models\StatutoryReportRun;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;

class StatutoryReportController extends Controller
{
    private AuditLogServiceInterface $auditLogs;

    private ResponseServiceInterface $response;

    public function __construct(AuditLogServiceInterface $auditLogs, ResponseServiceInterface $response)
    {
        $this->auditLogs = $auditLogs;
        $this->response = $response;
        $this->middleware('permission:view-payroll');
    }

    public function index()
    {
        return $this->response->successResponse('Statutory reports', StatutoryReportRun::query()->latest()->get());
    }

    public function store(StoreStatutoryReportRunRequest $request)
    {
        $items = PayrollItem::query()->where('payroll_period_id', $request->validated('payroll_period_id'))->get();
        $type = $request->validated('report_type');
        $columns = match ($type) {
            'sss' => ['sss_employee', 'sss_employer', 'sss_ec_employer'],
            'philhealth' => ['philhealth_employee', 'philhealth_employer'],
            'pagibig' => ['pagibig_employee', 'pagibig_employer'],
            'bir_1601c' => ['withholding_tax'],
        };
        $snapshot = ['format_version' => 1, 'source' => 'payroll_items', 'employee_count' => $items->count(), 'totals' => collect($columns)->mapWithKeys(fn (string $column) => [$column => number_format($items->sum($column), 2, '.', '')])->all()];
        $report = StatutoryReportRun::query()->create([...$request->validated(), 'status' => 'generated', 'snapshot' => $snapshot, 'generated_by' => $request->user()->getKey(), 'generated_at' => now()]);
        $this->auditLogs->insertLog($report, 'generate statutory report', ['report_type' => $type]);

        return $this->response->storeResponse('Statutory report', $report);
    }
}
