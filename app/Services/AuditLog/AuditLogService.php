<?php

namespace App\Services\AuditLog;

use App\Models\Logs\AuditLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use JsonSerializable;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AuditLogService implements AuditLogServiceInterface
{
    private const REDACTED = '[REDACTED]';

    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'secret',
        'api_key',
    ];

    public function insertLog($model, string $action, array $attr = []): void
    {
        try {
            $user = Auth::user();

            AuditLog::create(array_merge([
                'module' => $this->moduleName($model),
                'user_id' => $user?->id,
                'user_full_name' => $user?->full_name ?? 'System',
                'action' => $action,
                'payload' => $this->sanitize($attr),
                'result' => 'Success',
            ], $this->requestContext()));
        } catch (Throwable $exception) {
            // An unavailable audit trail must never roll back the business action.
            report($exception);
        }
    }

    public function loginLog(string $action, array $attr): void
    {
        try {
            $user = isset($attr['email'])
                ? User::where('email', $attr['email'])->first()
                : null;

            AuditLog::create(array_merge([
                'user_id' => $user?->id,
                'user_full_name' => $user?->full_name ?? ($attr['email'] ?? 'Unknown user'),
                'action' => $action,
                'module' => User::class,
                'payload' => $this->sanitize($attr),
                'result' => 'Success',
            ], $this->requestContext()));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function getLogsByDate(?string $from, ?string $to)
    {
        $fromDate = $from
            ? CarbonImmutable::parse($from)->startOfDay()
            : CarbonImmutable::now()->subDays(30)->startOfDay();
        $toDate = $to
            ? CarbonImmutable::parse($to)->endOfDay()
            : CarbonImmutable::now()->endOfDay();

        return AuditLog::query()
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->filter()
            ->orderByDesc('created_at')
            ->paginate((int) request()->input('limit', 10));
    }

    public function exportComplianceLogs(?string $from, ?string $to): StreamedResponse
    {
        [$fromDate, $toDate] = $this->dateRange($from, $to);
        $logs = AuditLog::query()
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
        $integrity = $this->verifyIntegrity();
        $filename = 'audit-log-'.$fromDate->toDateString().'-to-'.$toDate->toDateString().'.csv';

        return response()->streamDownload(function () use ($logs, $integrity): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Audit export version', '1']);
            fputcsv($handle, ['Integrity verified', $integrity['valid'] ? 'yes' : 'no']);
            fputcsv($handle, ['Integrity failures', $integrity['invalid_count']]);
            fputcsv($handle, []);
            fputcsv($handle, [
                'ID', 'Occurred at', 'Actor ID', 'Actor', 'Action', 'Module', 'Result',
                'IP address', 'HTTP method', 'Route', 'Payload', 'Previous hash', 'Integrity hash', 'Retention until',
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at?->toAtomString(),
                    $log->user_id,
                    $log->user_full_name,
                    $log->action,
                    $log->module,
                    $log->result,
                    $log->ip_address,
                    $log->http_method,
                    $log->route_name,
                    json_encode($log->payload, JSON_UNESCAPED_SLASHES),
                    $log->previous_hash,
                    $log->integrity_hash,
                    $log->retention_until?->toAtomString(),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function verifyIntegrity(): array
    {
        return $this->verifyIntegrityFor(AuditLog::query()->orderBy('created_at')->orderBy('id')->get());
    }

    private function moduleName(mixed $model): string
    {
        $class = is_object($model) ? $model::class : (string) $model;

        return $class;
    }

    private function dateRange(?string $from, ?string $to): array
    {
        return [
            $from ? CarbonImmutable::parse($from)->startOfDay() : CarbonImmutable::now()->subDays(30)->startOfDay(),
            $to ? CarbonImmutable::parse($to)->endOfDay() : CarbonImmutable::now()->endOfDay(),
        ];
    }

    private function verifyIntegrityFor(iterable $logs): array
    {
        $previousHash = null;
        $invalidCount = 0;

        foreach ($logs as $log) {
            if (! $log->integrity_hash) {
                continue;
            }

            $expectedHash = hash_hmac('sha256', $log->integrityPayload(), (string) config('audit.signing_key'));
            if (! hash_equals((string) $previousHash, (string) $log->previous_hash)
                || ! hash_equals($expectedHash, $log->integrity_hash)) {
                $invalidCount++;
            }
            $previousHash = $log->integrity_hash;
        }

        return ['valid' => $invalidCount === 0, 'invalid_count' => $invalidCount];
    }

    private function requestContext(): array
    {
        if (! app()->bound('request')) {
            return [];
        }

        $request = request();

        return [
            'ip_address' => $request->ip(),
            'http_method' => $request->method(),
            'route_name' => $request->route()?->getName(),
        ];
    }

    private function sanitize(mixed $value, string $key = null): mixed
    {
        if ($key !== null && $this->isSensitiveKey($key)) {
            return self::REDACTED;
        }

        if ($value instanceof Model) {
            $value = $value->toArray();
        } elseif ($value instanceof UploadedFile) {
            return [
                'name' => $value->getClientOriginalName(),
                'mime_type' => $value->getClientMimeType(),
                'size' => $value->getSize(),
            ];
        } elseif ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        } elseif ($value instanceof JsonSerializable) {
            $value = $value->jsonSerialize();
        }

        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $childKey => $childValue) {
                $sanitized[$childKey] = $this->sanitize($childValue, (string) $childKey);
            }

            return $sanitized;
        }

        if (is_object($value)) {
            return method_exists($value, '__toString')
                ? (string) $value
                : $value::class;
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = Str::lower(str_replace(['-', ' '], '_', $key));

        return in_array($normalized, self::SENSITIVE_KEYS, true)
            || Str::endsWith($normalized, ['_password', '_token', '_secret', '_api_key']);
    }
}
