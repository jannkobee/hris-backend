<?php

namespace Tests\Feature;

use App\Jobs\AdjustLeaveCredits;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\LeaveCredit;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Logs\AuditLog;
use App\Models\Note;
use App\Models\Organization;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Role;
use App\Models\ScheduledTask;
use App\Models\User;
use App\Models\WebhookSubscription;
use App\Services\AppSettings\AppSettingService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdversarialTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private TenantContext $tenants;

    private Organization $alpha;

    private Organization $beta;

    private User $alphaAdmin;

    private User $betaAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenants = app(TenantContext::class);
        $this->alpha = $this->tenants->organization();
        $this->alpha->update(['plan_code' => Organization::PLAN_ENTERPRISE]);
        $this->alphaAdmin = $this->adminFor($this->alpha, 'alpha-admin@example.test');

        $this->beta = Organization::create([
            'slug' => 'adversarial-beta',
            'name' => 'Adversarial Beta',
            'timezone' => 'Asia/Manila',
            'country_code' => 'PH',
            'plan_code' => Organization::PLAN_ENTERPRISE,
            'status' => Organization::STATUS_ACTIVE,
        ]);
        $this->betaAdmin = $this->adminFor($this->beta, 'beta-admin@example.test');
        $this->tenants->set($this->alpha);
    }

    public function test_list_endpoints_never_return_another_organizations_records(): void
    {
        $this->seedResourceMatrix($this->alpha, 'ALPHA-ONLY', $this->alphaAdmin);
        $this->seedResourceMatrix($this->beta, 'BETA-SECRET', $this->betaAdmin);

        $this->tenants->set($this->alpha);
        $this->actingAs($this->alphaAdmin, 'sanctum');

        $endpoints = [
            route('departments.index'),
            route('employees.index'),
            route('attendances.index'),
            route('leave-requests.index'),
            route('payroll.index'),
            route('notes.index'),
            route('announcements.index'),
            route('scheduled-tasks.index'),
            route('integrations.webhooks.index'),
            route('audit-logs.index'),
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint)->assertOk();
            $response->assertDontSee('BETA-SECRET', false);
        }

        $settings = $this->getJson(route('app-settings.index'))->assertOk();
        $this->assertSame(
            'ALPHA-ONLY Company',
            $settings->json('data.values')['organization.company_name']
        );
        $settings->assertDontSee('BETA-SECRET', false);
    }

    public function test_known_foreign_uuids_cannot_be_viewed_updated_or_deleted(): void
    {
        $foreignDepartment = $this->tenants->run(
            $this->beta,
            fn () => Department::create(['name' => 'BETA-SECRET Department'])
        );
        $foreignPeriod = $this->tenants->run(
            $this->beta,
            fn () => PayrollPeriod::create($this->payrollAttributes('BETA-SECRET Payroll', $this->betaAdmin))
        );

        $this->tenants->set($this->alpha);
        $this->actingAs($this->alphaAdmin, 'sanctum');

        $this->getJson(route('departments.show', $foreignDepartment))->assertUnprocessable();
        $this->putJson(route('departments.update', $foreignDepartment), [
            'name' => 'Compromised',
        ])->assertUnprocessable();
        $this->deleteJson(route('departments.destroy', $foreignDepartment))->assertUnprocessable();
        $this->getJson(route('payroll.show', $foreignPeriod))->assertNotFound();
        $this->get(route('payroll.export.csv', $foreignPeriod))->assertNotFound();

        $this->tenants->run($this->beta, function () use ($foreignDepartment, $foreignPeriod): void {
            $this->assertSame('BETA-SECRET Department', Department::findOrFail($foreignDepartment->id)->name);
            $this->assertSame('BETA-SECRET Payroll', PayrollPeriod::findOrFail($foreignPeriod->id)->name);
        });
    }

    public function test_foreign_employee_and_leave_type_relationships_are_rejected(): void
    {
        [$foreignEmployee, $foreignLeaveType] = $this->tenants->run($this->beta, function (): array {
            $employee = Employee::create([
                'user_id' => $this->betaAdmin->id,
                'employee_no' => 'BETA-SECRET-EMP',
                'hire_date' => '2026-01-01',
            ]);
            $leaveType = LeaveType::create([
                'name' => 'BETA-SECRET Leave',
                'default_days' => 10,
                'is_paid' => true,
            ]);

            return [$employee, $leaveType];
        });

        $this->tenants->set($this->alpha);
        $this->actingAs($this->alphaAdmin, 'sanctum')
            ->postJson(route('leave-requests.store'), [
                'employee_id' => $foreignEmployee->id,
                'leave_type_id' => $foreignLeaveType->id,
                'start_at' => '2026-09-10T09:00',
                'end_at' => '2026-09-10T18:00',
                'reason' => 'Cross-tenant attempt',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['employee_id', 'leave_type_id']);
    }

    public function test_foreign_private_file_cannot_be_downloaded_even_when_uuid_and_path_are_known(): void
    {
        Storage::fake('local');
        $foreignDocument = $this->tenants->run($this->beta, function (): EmployeeDocument {
            $employee = Employee::create([
                'user_id' => $this->betaAdmin->id,
                'employee_no' => 'BETA-SECRET-DOC',
            ]);
            $path = "employee-documents/{$employee->id}/secret.txt";
            Storage::disk('local')->put($path, 'BETA-SECRET file contents');

            return EmployeeDocument::create([
                'employee_id' => $employee->id,
                'category' => 'employment',
                'visibility' => 'hr_only',
                'title' => 'BETA-SECRET Document',
                'disk' => 'local',
                'path' => $path,
                'original_name' => 'secret.txt',
                'mime_type' => 'text/plain',
                'size' => 25,
                'uploaded_by' => $this->betaAdmin->id,
            ]);
        });

        $this->tenants->set($this->alpha);
        $this->actingAs($this->alphaAdmin, 'sanctum')
            ->get(route('employee-documents.download', $foreignDocument))
            ->assertNotFound();
    }

    public function test_payroll_export_contains_only_the_current_organizations_items(): void
    {
        $alphaPeriod = $this->payrollWithEmployee($this->alpha, $this->alphaAdmin, 'ALPHA-ONLY');
        $this->payrollWithEmployee($this->beta, $this->betaAdmin, 'BETA-SECRET');

        $this->tenants->set($this->alpha);
        $export = $this->actingAs($this->alphaAdmin, 'sanctum')
            ->get(route('payroll.export.csv', $alphaPeriod))
            ->assertOk();

        $content = $export->streamedContent();
        $this->assertStringContainsString('ALPHA-ONLY', $content);
        $this->assertStringNotContainsString('BETA-SECRET', $content);
    }

    public function test_additional_natural_keys_can_repeat_across_organizations(): void
    {
        foreach ([$this->alpha, $this->beta] as $organization) {
            $this->tenants->run($organization, function (): void {
                Department::create(['name' => 'Operations']);
                LeaveType::create(['name' => 'Vacation Leave', 'default_days' => 10, 'is_paid' => true]);
                ScheduledTask::create([
                    'name' => 'Daily reminders',
                    'command' => 'reminders:send',
                    'frequency' => 'daily',
                    'run_time' => '09:00',
                    'timezone' => 'Asia/Manila',
                    'is_active' => true,
                ]);
            });
        }

        foreach ([$this->alpha, $this->beta] as $organization) {
            $this->tenants->run($organization, function (): void {
                $this->assertSame(1, Department::where('name', 'Operations')->count());
                $this->assertSame(1, LeaveType::where('name', 'Vacation Leave')->count());
                $this->assertSame(1, ScheduledTask::where('name', 'Daily reminders')->count());
            });
        }
    }

    public function test_queued_leave_adjustment_restores_its_tenant_and_never_touches_a_matching_foreign_credit(): void
    {
        [$alphaRequest, $alphaCredit] = $this->leaveCreditScenario($this->alpha, $this->alphaAdmin, 'ALPHA-ONLY');
        [, $betaCredit] = $this->leaveCreditScenario($this->beta, $this->betaAdmin, 'BETA-SECRET');

        $this->tenants->clear();
        (new AdjustLeaveCredits($alphaRequest, 'deduct'))->handle($this->tenants);

        $this->tenants->run($this->alpha, function () use ($alphaCredit): void {
            $this->assertSame('1.00', LeaveCredit::findOrFail($alphaCredit->id)->used);
        });
        $this->tenants->run($this->beta, function () use ($betaCredit): void {
            $this->assertSame('0.00', LeaveCredit::findOrFail($betaCredit->id)->used);
        });
        $this->assertFalse($this->tenants->hasOrganization());
    }

    private function seedResourceMatrix(Organization $organization, string $marker, User $admin): void
    {
        $this->tenants->run($organization, function () use ($marker, $admin): void {
            Auth::setUser($admin);
            $department = Department::create(['name' => "{$marker} Department"]);
            $employee = Employee::create([
                'user_id' => $admin->id,
                'employee_no' => "{$marker}-EMP",
                'department_id' => $department->id,
                'hire_date' => '2026-01-01',
            ]);
            $leaveType = LeaveType::create([
                'name' => "{$marker} Leave",
                'default_days' => 10,
                'is_paid' => true,
            ]);

            Attendance::create([
                'employee_id' => $employee->id,
                'date' => '2026-09-01',
                'time_in' => '2026-09-01 01:00:00',
                'time_in_notes' => "{$marker} attendance",
            ]);
            LeaveRequest::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => '2026-09-10',
                'start_time' => '09:00',
                'end_date' => '2026-09-10',
                'end_time' => '18:00',
                'reason' => "{$marker} leave request",
                'status' => 'pending',
            ]);
            PayrollPeriod::create($this->payrollAttributes("{$marker} Payroll", $admin));
            Note::create(['title' => "{$marker} Note", 'content' => $marker]);
            Announcement::create([
                'title' => "{$marker} Announcement",
                'content' => "<p>{$marker}</p>",
                'published_at' => '2026-09-01',
                'is_active' => true,
                'created_by' => $admin->id,
            ]);
            ScheduledTask::create([
                'name' => "{$marker} Task",
                'command' => 'reminders:send',
                'frequency' => 'daily',
                'run_time' => '09:00',
                'timezone' => 'Asia/Manila',
                'is_active' => true,
            ]);
            WebhookSubscription::create([
                'name' => "{$marker} Webhook",
                'url' => 'https://example.test/webhook',
                'event_types' => ['employee.created'],
                'signing_secret' => "{$marker}-secret",
                'is_active' => true,
                'created_by' => $admin->id,
            ]);
            AuditLog::create([
                'user_id' => $admin->id,
                'user_full_name' => $marker,
                'action' => "{$marker} audit action",
                'module' => Department::class,
                'payload' => ['marker' => $marker],
                'result' => 'success',
            ]);
            app(AppSettingService::class)->update([
                'organization.company_name' => "{$marker} Company",
            ]);
        });
    }

    private function payrollWithEmployee(Organization $organization, User $admin, string $marker): PayrollPeriod
    {
        return $this->tenants->run($organization, function () use ($admin, $marker): PayrollPeriod {
            $employee = Employee::create([
                'employee_no' => "{$marker}-PAY",
                'basic_monthly_salary' => 30000,
                'pay_schedule' => 'semi_monthly',
            ]);
            $period = PayrollPeriod::create($this->payrollAttributes("{$marker} Export", $admin));
            PayrollItem::create([
                'payroll_period_id' => $period->id,
                'employee_id' => $employee->id,
                'basic_pay' => 1000,
                'gross_pay' => 1000,
                'total_deductions' => 0,
                'net_pay' => 1000,
                'calculation_snapshot' => ['marker' => $marker],
            ]);

            return $period;
        });
    }

    private function leaveCreditScenario(Organization $organization, User $admin, string $marker): array
    {
        return $this->tenants->run($organization, function () use ($admin, $marker): array {
            $employee = Employee::create([
                'user_id' => $admin->id,
                'employee_no' => "{$marker}-CREDIT",
            ]);
            $leaveType = LeaveType::create([
                'name' => "{$marker} Credit Leave",
                'default_days' => 10,
                'is_paid' => true,
            ]);
            $credit = LeaveCredit::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => 2026,
                'total_earned' => 10,
                'used' => 0,
            ]);
            $request = LeaveRequest::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => '2026-09-10',
                'start_time' => '09:00',
                'end_date' => '2026-09-10',
                'end_time' => '18:00',
                'reason' => $marker,
                'status' => 'approved',
            ]);

            return [$request, $credit];
        });
    }

    private function payrollAttributes(string $name, User $creator): array
    {
        return [
            'name' => $name,
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-15',
            'payout_date' => '2026-09-15',
            'frequency' => 'semi_monthly',
            'status' => 'draft',
            'created_by' => $creator->id,
        ];
    }

    private function adminFor(Organization $organization, string $email): User
    {
        return $this->tenants->run($organization, function () use ($email): User {
            $role = Role::create(['name' => 'Admin']);

            return User::factory()->create([
                'role_id' => $role->id,
                'email' => $email,
                'is_active' => true,
            ]);
        });
    }
}
