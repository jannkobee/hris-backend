<?php

namespace App\Services\Organizations;

use App\Models\Organization;
use App\Models\PlatformHealthSnapshot;
use App\Models\PlatformOperationLog;
use App\Models\PlatformSetting;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlatformHealthService
{
    private const THRESHOLDS_KEY = 'health.thresholds';

    private MaintenanceMode $maintenanceMode;

    public function __construct(MaintenanceMode $maintenanceMode)
    {
        $this->maintenanceMode = $maintenanceMode;
    }

    public function health(): array
    {
        $thresholds = config('platform.health.thresholds', []);
        $settingsCheck = $this->check(function () use (&$thresholds): void {
            $thresholds = $this->settings();
        });
        $checks = [
            'database' => $this->check(function (): void {
                DB::select('select 1');
            }),
            'cache' => $this->check(function (): void {
                $key = 'platform-health-check:'.Str::uuid();
                try {
                    Cache::put($key, 'ok', 10);
                    if (Cache::get($key) !== 'ok') {
                        throw new \RuntimeException('Cache health probe failed.');
                    }
                } finally {
                    Cache::forget($key);
                }
            }),
            'storage' => $this->check(function (): void {
                $disk = Storage::disk(config('filesystems.default'));
                $key = '.platform-health/'.Str::uuid();
                try {
                    if (! $disk->put($key, 'ok') || $disk->get($key) !== 'ok') {
                        throw new \RuntimeException('Storage health probe failed.');
                    }
                } finally {
                    if (! $disk->delete($key)) {
                        throw new \RuntimeException('Storage health probe cleanup failed.');
                    }
                }
            }),
            'settings' => $settingsCheck,
            'queue' => $this->check(fn () => $this->queueCheck($this->failedJobs(), $thresholds)),
            'mail' => $this->mailCheck(),
            'maintenance' => [
                'status' => $this->maintenanceMode->active() ? 'active' : 'inactive',
                'retry_after' => $this->maintenanceMode->active()
                    ? ($this->maintenanceMode->data()['retry'] ?? null)
                    : null,
            ],
            'organizations' => $this->check(fn () => Organization::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all()),
        ];
        $status = $this->status($checks);
        $health = [
            'status' => $status,
            'checks' => $checks,
            'thresholds' => $thresholds,
            'captured_at' => now()->toIso8601String(),
        ];

        // Monitoring must still return the checks when persistence is unavailable.
        $snapshotCheck = $this->check(function () use ($health, $thresholds): void {
            $this->recordSnapshot($health, $thresholds);
        });
        $health['checks']['snapshot_storage'] = $snapshotCheck;
        $health['status'] = $this->status($health['checks']);

        // Preserve the original top-level check fields for existing API clients.
        return array_merge($checks, $health);
    }

    public function settings(): array
    {
        $defaults = config('platform.health.thresholds', []);
        $stored = PlatformSetting::query()->where('key', self::THRESHOLDS_KEY)->first()?->value;
        $stored = is_array($stored) ? $stored : [];

        return [
            'failed_jobs_warning' => (int) ($stored['failed_jobs_warning'] ?? $defaults['failed_jobs_warning'] ?? 1),
            'failed_jobs_critical' => (int) ($stored['failed_jobs_critical'] ?? $defaults['failed_jobs_critical'] ?? 5),
            'snapshot_interval_minutes' => (int) ($stored['snapshot_interval_minutes'] ?? $defaults['snapshot_interval_minutes'] ?? 5),
        ];
    }

    public function updateSettings(array $attributes): array
    {
        $settings = array_merge($this->settings(), $attributes);
        PlatformSetting::query()->updateOrCreate(
            ['key' => self::THRESHOLDS_KEY],
            ['value' => $settings]
        );
        $this->recordOperation('platform health thresholds updated', $settings);

        return $settings;
    }

    public function history(int $limit): array
    {
        return PlatformHealthSnapshot::query()
            ->latest('captured_at')
            ->limit(max(1, min(100, $limit)))
            ->get(['id', 'status', 'checks', 'captured_at'])
            ->map(fn (PlatformHealthSnapshot $snapshot): array => [
                'id' => $snapshot->id,
                'status' => $snapshot->status,
                'checks' => $snapshot->checks,
                'captured_at' => $snapshot->captured_at?->toIso8601String(),
            ])
            ->all();
    }

    public function setMaintenance(bool $enabled, ?int $retryAfter, ?string $reason): array
    {
        if ($enabled) {
            $this->maintenanceMode->activate([
                'time' => now()->timestamp,
                'status' => 503,
                'retry' => $retryAfter ?? 300,
            ]);
        } else {
            $this->maintenanceMode->deactivate();
        }

        $this->recordOperation(
            $enabled ? 'platform maintenance enabled' : 'platform maintenance disabled',
            [
                'reason' => $reason,
                'retry_after' => $enabled ? ($retryAfter ?? 300) : null,
            ]
        );

        return [
            'active' => $this->maintenanceMode->active(),
            'retry_after' => $this->maintenanceMode->active()
                ? ($this->maintenanceMode->data()['retry'] ?? null)
                : null,
        ];
    }

    private function queueCheck(int $failedJobs, array $thresholds): array
    {
        $status = $failedJobs >= $thresholds['failed_jobs_critical']
            ? 'failed'
            : ($failedJobs >= $thresholds['failed_jobs_warning'] ? 'warning' : 'ok');

        return [
            'status' => $status,
            'driver' => config('queue.default'),
            'failed_jobs' => $failedJobs,
        ];
    }

    private function mailCheck(): array
    {
        $mailer = (string) config('mail.default');
        $transport = (string) config("mail.mailers.{$mailer}.transport", '');

        return [
            'status' => $transport === '' ? 'failed' : 'configured',
            'mailer' => $mailer,
            'transport' => $transport,
            'delivery_mode' => in_array($transport, ['array', 'log'], true) ? 'local' : 'external',
        ];
    }

    private function failedJobs(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            throw new \RuntimeException('Failed-job monitoring table is unavailable.');
        }

        return (int) DB::table('failed_jobs')->count();
    }

    private function status(array $checks): string
    {
        if ($checks['maintenance']['status'] === 'active') {
            return 'maintenance';
        }

        if (collect($checks)->contains(fn (mixed $check): bool => is_array($check) && ($check['status'] ?? null) === 'failed')) {
            return 'degraded';
        }

        if (collect($checks)->contains(fn (mixed $check): bool => is_array($check) && ($check['status'] ?? null) === 'warning')) {
            return 'warning';
        }

        return 'ok';
    }

    private function check(callable $callback): array
    {
        try {
            $result = $callback();

            return is_array($result) ? $result : ['status' => 'ok'];
        } catch (\Throwable $exception) {
            report($exception);

            return ['status' => 'failed'];
        }
    }

    private function recordSnapshot(array $health, array $thresholds): void
    {
        $latest = PlatformHealthSnapshot::query()->latest('captured_at')->first();
        if ($latest?->captured_at?->greaterThan(now()->subMinutes($thresholds['snapshot_interval_minutes']))) {
            return;
        }

        PlatformHealthSnapshot::query()->create([
            'status' => $health['status'],
            'checks' => $health['checks'],
            'captured_at' => now(),
        ]);
    }

    private function recordOperation(string $action, array $payload): void
    {
        PlatformOperationLog::query()->create([
            'action' => $action,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }
}
