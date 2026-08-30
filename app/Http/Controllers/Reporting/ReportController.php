<?php

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\RunReportRequest;
use App\Http\Requests\SavedReportRequest;
use App\Models\SavedReport;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Reporting\OperationalReportService;
use App\Services\Utils\ResponseServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private OperationalReportService $reports;

    private ResponseServiceInterface $response;

    private AuditLogServiceInterface $auditLogs;

    public function __construct(
        OperationalReportService $reports,
        ResponseServiceInterface $response,
        AuditLogServiceInterface $auditLogs,
    ) {
        $this->reports = $reports;
        $this->response = $response;
        $this->auditLogs = $auditLogs;
        $this->middleware('permission:view-reports')->only(['run', 'export']);
        $this->middleware('permission:manage-reports')->except(['run', 'export']);
    }

    public function run(RunReportRequest $request)
    {
        $data = $request->validated();

        return $this->response->successResponse('Report generated', $this->reports->run(
            $data['report_type'], $data['from'], $data['to']
        ));
    }

    public function export(RunReportRequest $request): StreamedResponse
    {
        $data = $request->validated();
        $report = $this->reports->run($data['report_type'], $data['from'], $data['to']);
        $this->auditLogs->insertLog($request->user(), 'export report', ['report_type' => $data['report_type'], 'from' => $data['from'], 'to' => $data['to']]);

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $report['columns']);
            foreach ($report['rows'] as $row) {
                fputcsv($handle, array_map(fn ($column) => $row[$column] ?? null, $report['columns']));
            }
            fclose($handle);
        }, str($data['report_type'] ?? 'report')->slug()->append('.csv'), ['Content-Type' => 'text/csv']);
    }

    public function index()
    {
        return $this->response->successResponse('Saved reports', SavedReport::query()->with('creator:id,first_name,last_name')->latest()->get());
    }

    public function store(SavedReportRequest $request)
    {
        $data = $request->validated();
        $report = SavedReport::create($data + [
            'created_by' => $request->user()->id,
            'next_delivery_at' => empty($data['delivery_frequency']) ? null : now(),
        ]);

        return $this->response->storeResponse('Saved report', $report);
    }

    public function update(SavedReportRequest $request, SavedReport $savedReport)
    {
        $data = $request->validated();
        $savedReport->update($data + [
            'next_delivery_at' => empty($data['delivery_frequency'])
                ? null
                : ($savedReport->next_delivery_at ?? now()),
        ]);

        return $this->response->updateResponse('Saved report', $savedReport->fresh());
    }

    public function destroy(SavedReport $savedReport)
    {
        $savedReport->delete();

        return $this->response->deleteResponse('Saved report', true);
    }
}
