<?php

namespace App\Http\Controllers\AuditLog;

use App\Http\Controllers\Controller;
use App\Services\AuditLog\AuditLogServiceInterface;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    private $auditLogService;

    public function __construct(AuditLogServiceInterface $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    public function index(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');

        return $this->auditLogService->getLogsByDate($from, $to);
    }
}
