<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PlatformHealthController extends Controller
{
    private ResponseServiceInterface $response;

    public function __construct(ResponseServiceInterface $response)
    {
        $this->response = $response;
    }

    public function show()
    {
        $checks = [
            'database' => $this->check(fn () => DB::select('select 1')),
            'cache' => $this->check(function (): void {
                Cache::put('platform-health-check', 'ok', 10);
                Cache::forget('platform-health-check');
            }),
            'storage' => $this->check(function (): void {
                Storage::disk(config('filesystems.default'))->exists('.platform-health-check');
            }),
            'queue' => ['status' => config('queue.default') === 'sync' ? 'sync' : 'configured', 'driver' => config('queue.default')],
        ];
        $checks['organizations'] = Organization::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $checks['status'] = collect($checks)->contains(fn ($check) => is_array($check) && ($check['status'] ?? null) === 'failed') ? 'degraded' : 'ok';

        return $this->response->successResponse('Platform health', $checks);
    }

    private function check(callable $callback): array
    {
        try {
            $callback();

            return ['status' => 'ok'];
        } catch (\Throwable $exception) {
            report($exception);

            return ['status' => 'failed'];
        }
    }
}
