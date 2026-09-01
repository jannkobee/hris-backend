<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollPeriod;
use App\Models\Role;
use App\Models\User;
use App\Services\Payroll\PayrollWorkSummaryService;
use Carbon\CarbonPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeRecordsAndPayrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_view_their_own_201_file_uploaded_by_hr(): void
    {
        Storage::fake('local');
        $admin = $this->userWithRole('Admin');
        $checker = $this->userWithRole('Admin');
        $payor = $this->userWithRole('Admin');
        $employeeUser = $this->userWithRole('User');
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_no' => 'EMP-201',
        ]);

        $upload = $this->actingAs($admin, 'sanctum')->post(
            route('employees.documents.store', $employee),
            [
                'category' => 'employment',
                'visibility' => 'employee',
                'title' => 'Employment contract',
                'file' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
            ]
        )->assertCreated();

        $documentId = $upload->json('data.id');
        $this->actingAs($employeeUser, 'sanctum')
            ->getJson(route('employees.documents.index', $employee))
            ->assertOk()
            ->assertJsonPath('data.0.id', $documentId);

        $this->actingAs($employeeUser, 'sanctum')
            ->get(route('employee-documents.download', $documentId))
            ->assertOk();
    }

    public function test_profile_photo_is_stored_privately_and_returned_to_authenticated_users(): void
    {
        Storage::fake('local');
        $user = $this->userWithRole('User');

        $this->actingAs($user, 'sanctum')->post(
            route('profile.photo.upload'),
            ['photo' => UploadedFile::fake()->image('avatar.jpg', 200, 200)]
        )->assertAccepted();

        $user->refresh();
        Storage::disk('local')->assertExists($user->profile_photo_path);
        $this->actingAs($user, 'sanctum')
            ->get(route('users.profile-photo', $user))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
    }

    public function test_admin_can_process_approve_and_mark_payroll_paid(): void
    {
        $admin = $this->userWithRole('Admin');
        $checker = $this->userWithRole('Admin');
        $payor = $this->userWithRole('Admin');
        $employeeUser = $this->userWithRole('User');
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_no' => 'EMP-PAY',
            'basic_monthly_salary' => 30000,
            'pay_schedule' => 'semi_monthly',
        ]);
        foreach (CarbonPeriod::create('2026-08-01', '2026-08-15') as $date) {
            if ($date->isWeekend()) {
                continue;
            }
            Attendance::create([
                'employee_id' => $employee->id,
                'date' => $date->toDateString(),
                // Attendance timestamps are stored in UTC; these represent
                // 09:00 and 18:00 in the default Asia/Manila company timezone.
                'time_in' => $date->copy()->setTime(1, 0),
                'time_out' => $date->copy()->setTime(10, 0),
            ]);
        }

        $periodId = $this->actingAs($admin, 'sanctum')->postJson(route('payroll.store'), [
            'name' => 'August 1st cutoff',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-15',
            'payout_date' => '2026-08-15',
            'frequency' => 'semi_monthly',
        ])->assertCreated()->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->postJson(route('payroll.process', $periodId))
            ->assertAccepted()
            ->assertJsonPath('data.items.0.net_pay', '13271.30');

        $export = $this->actingAs($admin, 'sanctum')
            ->get(route('payroll.export.csv', $periodId))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('EMP-PAY', $export->streamedContent());

        $this->actingAs($checker, 'sanctum')->postJson(route('payroll.approve', $periodId))->assertAccepted();
        $this->actingAs($checker, 'sanctum')->postJson(route('payroll.lock', $periodId))->assertAccepted();
        $this->actingAs($payor, 'sanctum')->postJson(route('payroll.mark-paid', $periodId))->assertAccepted();

        $this->assertSame('paid', PayrollPeriod::findOrFail($periodId)->status);
        $this->actingAs($employeeUser, 'sanctum')
            ->getJson(route('payroll.show', $periodId))
            ->assertOk()
            ->assertJsonCount(1, 'data.items');
    }

    public function test_payroll_work_summary_distinguishes_paid_and_unpaid_leave(): void
    {
        $employee = Employee::create(['employee_no' => 'EMP-LEAVE']);
        $paid = LeaveType::create(['name' => 'Vacation', 'default_days' => 10, 'is_paid' => true]);
        $unpaid = LeaveType::create(['name' => 'Unpaid leave', 'default_days' => 10, 'is_paid' => false]);

        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $paid->id,
            'start_date' => '2026-08-03',
            'start_time' => '09:00',
            'end_date' => '2026-08-03',
            'end_time' => '18:00',
            'reason' => 'Vacation',
            'status' => 'approved',
        ]);
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $unpaid->id,
            'start_date' => '2026-08-04',
            'start_time' => '09:00',
            'end_date' => '2026-08-04',
            'end_time' => '13:00',
            'reason' => 'Personal',
            'status' => 'approved',
        ]);

        $summary = app(PayrollWorkSummaryService::class)
            ->summarize($employee, '2026-08-03', '2026-08-04');

        $this->assertSame(2.0, $summary['scheduled_days']);
        $this->assertSame(1.0, $summary['paid_leave_days']);
        $this->assertSame(0.5, $summary['unpaid_leave_days']);
        $this->assertSame(0.5, $summary['absent_days']);
        $this->assertCount(1, $summary['exceptions']);
    }

    public function test_payroll_approval_requires_attendance_exceptions_to_be_acknowledged(): void
    {
        $admin = $this->userWithRole('Admin');
        $checker = $this->userWithRole('Admin');
        $employeeUser = $this->userWithRole('User');
        Employee::create([
            'user_id' => $employeeUser->id,
            'employee_no' => 'EMP-EXCEPTION',
            'basic_monthly_salary' => 30000,
            'pay_schedule' => 'semi_monthly',
        ]);

        $periodId = $this->actingAs($admin, 'sanctum')->postJson(route('payroll.store'), [
            'name' => 'Exception cutoff',
            'date_from' => '2026-08-03',
            'date_to' => '2026-08-03',
            'payout_date' => '2026-08-03',
            'frequency' => 'semi_monthly',
        ])->assertCreated()->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->postJson(route('payroll.process', $periodId))
            ->assertAccepted()
            ->assertJsonPath('data.items.0.exceptions.0.code', 'missing_attendance');

        $this->actingAs($employeeUser, 'sanctum')
            ->getJson(route('payroll.show', $periodId))
            ->assertForbidden();

        $this->actingAs($checker, 'sanctum')
            ->postJson(route('payroll.approve', $periodId))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('exceptions');

        $this->actingAs($admin, 'sanctum')
            ->postJson(route('payroll.acknowledge-exceptions', $periodId))
            ->assertAccepted()
            ->assertJsonPath('data.acknowledged', 1);

        $this->actingAs($checker, 'sanctum')
            ->postJson(route('payroll.approve', $periodId))
            ->assertAccepted();

        $this->actingAs($employeeUser, 'sanctum')
            ->getJson(route('payroll.show', $periodId))
            ->assertOk();
    }

    public function test_payroll_work_summary_calculates_late_and_undertime_in_company_timezone(): void
    {
        $employee = Employee::create(['employee_no' => 'EMP-TIME']);
        Attendance::create([
            'employee_id' => $employee->id,
            'date' => '2026-08-03',
            'time_in' => '2026-08-03 01:15:00',
            'time_out' => '2026-08-03 09:30:00',
        ]);

        $summary = app(PayrollWorkSummaryService::class)
            ->summarize($employee, '2026-08-03', '2026-08-03');

        $this->assertSame(15, $summary['late_minutes']);
        $this->assertSame(30, $summary['undertime_minutes']);
        $this->assertSame(1.0, $summary['days_worked']);
        $this->assertEmpty($summary['exceptions']);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
