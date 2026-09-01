<?php

namespace App\Services\AuditLog;

interface AuditLogServiceInterface
{
    public function insertLog($model, string $action, array $attr = []);

    public function loginLog(string $action, array $attr);

    public function getLogsByDate(?string $from, ?string $to);

    public function exportComplianceLogs(?string $from, ?string $to): \Symfony\Component\HttpFoundation\StreamedResponse;

    public function verifyIntegrity(): array;
}
