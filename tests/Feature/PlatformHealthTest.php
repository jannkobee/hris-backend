<?php

namespace Tests\Feature;

use App\Models\PlatformHealthSnapshot;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Never create or remove the running application's maintenance file.
        $this->app->instance(MaintenanceMode::class, new class implements MaintenanceMode
        {
            private array $payload = [];

            public function activate(array $payload): void
            {
                $this->payload = $payload;
            }

            public function deactivate(): void
            {
                $this->payload = [];
            }

            public function active(): bool
            {
                return $this->payload !== [];
            }

            public function data(): array
            {
                return $this->payload;
            }
        });
    }

    public function test_history_rejects_unbounded_limits(): void
    {
        config()->set('platform.provisioning_key', 'platform-test-key');

        foreach ([-1, 0, 101, 'invalid'] as $limit) {
            $this->platformRequest()
                ->getJson(route('platform.health.history', ['limit' => $limit]))
                ->assertUnprocessable();
        }
    }

    public function test_health_still_reports_when_snapshot_storage_is_unavailable(): void
    {
        config()->set('platform.provisioning_key', 'platform-test-key');
        Schema::drop('platform_health_snapshots');

        $this->platformRequest()->getJson(route('platform.health.show'))
            ->assertOk()
            ->assertJsonPath('data.status', 'degraded')
            ->assertJsonPath('data.checks.snapshot_storage.status', 'failed')
            ->assertJsonPath('data.checks.database.status', 'ok');
    }

    public function test_platform_mutations_require_operator_credentials(): void
    {
        config()->set('platform.provisioning_key', 'platform-test-key');

        $this->patchJson(route('platform.maintenance.update'), ['enabled' => false])
            ->assertUnauthorized();
        $this->patchJson(route('platform.health.settings.update'), [
            'failed_jobs_warning' => 1,
            'failed_jobs_critical' => 2,
            'snapshot_interval_minutes' => 5,
        ])->assertUnauthorized();
        $this->assertDatabaseCount('platform_operation_logs', 0);
        $this->assertFalse(app()->isDownForMaintenance());
    }

    public function test_platform_health_exposes_operational_checks_and_history_only_to_platform_operators(): void
    {
        config()->set('platform.provisioning_key', 'platform-test-key');
        config()->set('mail.default', 'array');
        config()->set('mail.mailers.array.transport', 'array');

        $this->getJson(route('platform.health.show'))->assertUnauthorized();

        $this->platformRequest()
            ->getJson(route('platform.health.show'))
            ->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.database.status', 'ok')
            ->assertJsonPath('data.checks.database.status', 'ok')
            ->assertJsonPath('data.checks.mail.delivery_mode', 'local')
            ->assertJsonPath('data.checks.maintenance.status', 'inactive');

        $this->assertSame(1, PlatformHealthSnapshot::query()->count());
        $this->platformRequest()
            ->getJson(route('platform.health.history'))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_platform_operator_can_configure_failed_job_thresholds_and_receive_a_warning(): void
    {
        config()->set('platform.provisioning_key', 'platform-test-key');

        $this->platformRequest()
            ->patchJson(route('platform.health.settings.update'), [
                'failed_jobs_warning' => 1,
                'failed_jobs_critical' => 2,
                'snapshot_interval_minutes' => 15,
            ])
            ->assertAccepted()
            ->assertJsonPath('data.failed_jobs_critical', 2);

        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'sync',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Example failure',
            'failed_at' => now(),
        ]);

        $this->platformRequest()
            ->getJson(route('platform.health.show'))
            ->assertOk()
            ->assertJsonPath('data.status', 'warning')
            ->assertJsonPath('data.checks.queue.failed_jobs', 1)
            ->assertJsonPath('data.checks.queue.status', 'warning');
    }

    public function test_platform_operator_can_safely_enable_and_disable_maintenance_mode(): void
    {
        config()->set('platform.provisioning_key', 'platform-test-key');

        try {
            $this->platformRequest()
                ->patchJson(route('platform.maintenance.update'), [
                    'enabled' => true,
                    'retry_after' => 120,
                    'reason' => 'Applying a payroll platform update.',
                ])
                ->assertAccepted()
                ->assertJsonPath('data.active', true)
                ->assertJsonPath('data.retry_after', 120);

            $this->assertTrue(app()->isDownForMaintenance());
            $this->platformRequest()
                ->getJson(route('platform.health.show'))
                ->assertOk()
                ->assertJsonPath('data.status', 'maintenance');

            $this->platformRequest()
                ->patchJson(route('platform.maintenance.update'), ['enabled' => false])
                ->assertAccepted()
                ->assertJsonPath('data.active', false);
        } finally {
            app()->maintenanceMode()->deactivate();
        }

        $this->assertFalse(app()->isDownForMaintenance());
    }

    private function platformRequest()
    {
        return $this->withHeader('X-Platform-Provisioning-Key', 'platform-test-key');
    }
}
