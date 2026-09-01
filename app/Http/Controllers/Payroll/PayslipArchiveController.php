<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use App\Models\PayslipArchive;
use App\Services\AuditLog\AuditLogServiceInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PayslipArchiveController extends Controller
{
    private AuditLogServiceInterface $auditLogs;

    public function __construct(AuditLogServiceInterface $auditLogs)
    {
        $this->auditLogs = $auditLogs;
        $this->middleware('permission:manage-payroll')->only('store');
    }

    public function store(Request $request, PayrollItem $item)
    {
        abort_if(PayslipArchive::query()->where('payroll_item_id', $item->getKey())->exists(), 409, 'This payslip is already archived.');
        abort_if(! $item->period || ! $item->period->isLocked(), 409, 'A payslip can only be archived after payroll is locked.');

        $item->loadMissing('employee.user', 'period');
        $pdf = Pdf::loadView('payroll.payslip-pdf', ['item' => $item, 'period' => $item->period, 'snapshot' => $item->locked_snapshot ?: $item->toArray()])->setPaper('a4');
        $contents = $pdf->output();
        $disk = config('filesystems.default');
        $path = 'payslip-archives/'.$item->organization_id.'/'.$item->getKey().'.pdf';
        Storage::disk($disk)->put($path, $contents, ['visibility' => 'private']);
        $archive = PayslipArchive::query()->create(['payroll_item_id' => $item->getKey(), 'disk' => $disk, 'path' => $path, 'checksum' => hash('sha256', $contents), 'archived_by' => $request->user()->getKey(), 'archived_at' => now()]);
        $this->auditLogs->insertLog($archive, 'archive payslip pdf', ['payroll_item_id' => $item->getKey()]);

        return response()->json(['message' => 'Payslip archived successfully.', 'data' => $archive], 201);
    }

    public function download(Request $request, PayslipArchive $archive)
    {
        $item = PayrollItem::query()->with('employee')->findOrFail($archive->payroll_item_id);
        if (! $request->user()->hasPermission('view-payroll') && $request->user()->employee?->getKey() !== $item->employee_id) {
            throw new AuthorizationException('You cannot download this payslip.');
        }
        $this->auditLogs->insertLog($archive, 'download archived payslip');

        return Storage::disk($archive->disk)->download($archive->path, 'payslip-'.$item->employee_id.'.pdf', ['Content-Type' => 'application/pdf']);
    }
}
