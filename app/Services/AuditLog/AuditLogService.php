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

    private function moduleName(mixed $model): string
    {
        $class = is_object($model) ? $model::class : (string) $model;

        return $class;
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

    private function sanitize(mixed $value, ?string $key = null): mixed
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
