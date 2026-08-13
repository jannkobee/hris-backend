<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\JobGrade;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Overtime;
use App\Models\PayrollPeriod;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Services\AppSettings\AppSettingService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class PayrollDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! Role::query()->where('name', 'User')->exists()) {
            $this->call(RoleSeeder::class);
        }
        if (! User::query()->whereHas('role', fn ($query) => $query->whereIn('name', ['Super Admin', 'Admin']))->exists()) {
            $this->call(AdminSeeder::class);
        }
        if (! EmploymentStatus::query()->where('name', 'Regular')->exists()) {
            $this->call(EmploymentStatusSeeder::class);
        }
        if (! Department::query()->where('name', 'Information Technology')->exists()) {
            $this->call(DepartmentSeeder::class);
        }
        if (! Position::query()->whereHas('department', fn ($query) => $query->where('name', 'Information Technology'))->exists()) {
            $this->call(PositionSeeder::class);
        }
        if (! JobGrade::query()->where('code', 'JG-03')->exists()) {
            $this->call(JobGradeSeeder::class);
        }
        if (! LeaveType::query()->where('name', 'Vacation Leave')->exists()) {
            $this->call(LeaveTypeSeeder::class);
        }

        [$from, $to, $payout] = $this->previousSemiMonthlyPeriod();
        $timezone = app(AppSettingService::class)->get('organization.timezone', config('app.timezone'));
        /** @var Role $role */
        $role = Role::query()->where('name', 'User')->firstOrFail();
        /** @var EmploymentStatus $status */
        $status = EmploymentStatus::query()->where('name', 'Regular')->firstOrFail();
        /** @var Department $department */
        $department = Department::query()->where('name', 'Information Technology')->firstOrFail();
        /** @var Position $position */
        $position = Position::query()->where('department_id', $department->id)->firstOrFail();
        /** @var JobGrade $grade */
        $grade = JobGrade::query()->where('code', 'JG-03')->firstOrFail();
        /** @var User $approver */
        $approver = User::query()->whereHas('role', fn ($query) => $query->whereIn('name', ['Super Admin', 'Admin']))->firstOrFail();

        $profiles = [
            ['first_name' => 'Alyssa', 'last_name' => 'Santos', 'salary' => 32000, 'pattern' => 'complete'],
            ['first_name' => 'Miguel', 'last_name' => 'Reyes', 'salary' => 38000, 'pattern' => 'late'],
            ['first_name' => 'Camille', 'last_name' => 'Dela Cruz', 'salary' => 45000, 'pattern' => 'leave'],
            ['first_name' => 'Joshua', 'last_name' => 'Garcia', 'salary' => 52000, 'pattern' => 'overtime'],
            ['first_name' => 'Bianca', 'last_name' => 'Mendoza', 'salary' => 60000, 'pattern' => 'absence'],
            ['first_name' => 'Nathan', 'last_name' => 'Flores', 'salary' => 70000, 'pattern' => 'undertime'],
        ];

        DB::transaction(function () use ($profiles, $role, $status, $department, $position, $grade, $approver, $from, $to, $payout, $timezone): void {
            foreach ($profiles as $index => $profile) {
                /** @var User $user */
                $user = User::query()->updateOrCreate(
                    ['email' => 'payroll.demo.'.($index + 1).'@hris.test'],
                    [
                        'role_id' => $role->id,
                        'first_name' => $profile['first_name'],
                        'middle_name' => null,
                        'last_name' => $profile['last_name'],
                        'gender' => $index % 2 ? 'Male' : 'Female',
                        'birthday' => Carbon::create(1990 + $index, 2 + $index, 10 + $index)->toDateString(),
                        'password' => Hash::make('password'),
                    ]
                );

                $employeeNo = 'PAY-DEMO-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
                $employee = $this->upsertDemoEmployee($user, $employeeNo, [
                    'hire_date' => $from->copy()->subYears(2)->toDateString(),
                    'employment_status_id' => $status->id,
                    'department_id' => $department->id,
                    'position_id' => $position->id,
                    'job_grade_id' => $grade->id,
                    'basic_monthly_salary' => $profile['salary'],
                    'pay_schedule' => 'semi_monthly',
                ]);

                $this->seedAttendance($employee, $profile['pattern'], $from, $to, $timezone);
                $this->seedScenarioRecords($employee, $profile['pattern'], $approver, $from, $to, $timezone);
            }

            /** @var PayrollPeriod $period */
            $period = PayrollPeriod::query()->firstOrNew(['name' => $this->periodName($from, $to)]);
            if ($period->exists) {
                $period->items()->delete();
            }
            $period->fill([
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'payout_date' => $payout->toDateString(),
                'frequency' => 'semi_monthly',
                'status' => 'draft',
                'created_by' => $approver->id,
                'approved_by' => null,
                'processed_at' => null,
                'approved_at' => null,
                'paid_at' => null,
                'total_gross' => 0,
                'total_deductions' => 0,
                'total_net' => 0,
            ])->save();
        });

        $this->command?->info('Payroll demo data created. Login: payroll.demo.1@hris.test / password');
        $this->command?->info('Open Payroll and generate the draft period: '.$from->toDateString().' to '.$to->toDateString());
    }

    private function upsertDemoEmployee(User $user, string $employeeNo, array $attributes): Employee
    {
        /** @var Employee|null $employeeForUser */
        $employeeForUser = Employee::query()->where('user_id', $user->id)->first();
        /** @var Employee|null $employeeForNumber */
        $employeeForNumber = Employee::query()->where('employee_no', $employeeNo)->first();

        if ($employeeForUser instanceof Employee
            && $employeeForNumber instanceof Employee
            && $employeeForUser->getKey() !== $employeeForNumber->getKey()) {
            throw new RuntimeException("Demo user {$user->email} and employee number {$employeeNo} refer to different employee records.");
        }

        $employee = $employeeForUser ?? $employeeForNumber;

        if ($employee && $employee->user_id !== $user->id) {
            $ownerEmail = $employee->user?->email;
            if (! is_string($ownerEmail) || ! str_starts_with($ownerEmail, 'payroll.demo.')) {
                throw new RuntimeException("Employee number {$employeeNo} is already assigned to a non-demo employee.");
            }
        }

        $employee ??= new Employee();
        $employee->fill([
            ...$attributes,
            'user_id' => $user->id,
            'employee_no' => $employeeNo,
        ])->save();

        return $employee;
    }

    private function periodName(Carbon $from, Carbon $to): string
    {
        return 'Demo Payroll - '.$from->format('M j').' to '.$to->format('M j, Y');
    }

    private function seedAttendance(Employee $employee, string $pattern, Carbon $from, Carbon $to, string $timezone): void
    {
        $weekdays = collect(CarbonPeriod::create($from, $to))->filter(fn (Carbon $date) => $date->isWeekday())->values();
        foreach ($weekdays as $index => $date) {
            if (($pattern === 'leave' && $index === 2) || ($pattern === 'absence' && $index === 3)) {
                continue;
            }

            $minutesLate = $pattern === 'late' && in_array($index, [1, 4], true) ? 25 : 0;
            $minutesEarly = $pattern === 'undertime' && $index === 3 ? 90 : 0;
            $timeIn = Carbon::parse($date->toDateString().' 09:00', $timezone)->addMinutes($minutesLate);
            $timeOut = Carbon::parse($date->toDateString().' 18:00', $timezone)->subMinutes($minutesEarly);

            Attendance::query()->updateOrCreate(
                ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                [
                    'time_in' => $timeIn->utc(),
                    'time_out' => $timeOut->utc(),
                    'time_in_notes' => 'Generated payroll demo attendance',
                    'time_out_notes' => 'Generated payroll demo attendance',
                    'ip_address' => '127.0.0.1',
                    'time_out_ip_address' => '127.0.0.1',
                ]
            );
        }
    }

    private function seedScenarioRecords(Employee $employee, string $pattern, ?User $approver, Carbon $from, Carbon $to, string $timezone): void
    {
        $weekdays = collect(CarbonPeriod::create($from, $to))->filter(fn (Carbon $date) => $date->isWeekday())->values();
        if ($pattern === 'leave' && $weekdays->get(2)) {
            $date = $weekdays->get(2)->toDateString();
            LeaveRequest::query()->updateOrCreate(
                ['employee_id' => $employee->id, 'start_date' => $date, 'end_date' => $date],
                [
                    'leave_type_id' => LeaveType::query()->where('name', 'Vacation Leave')->value('id'),
                    'start_time' => '09:00',
                    'end_time' => '18:00',
                    'reason' => 'Generated paid leave for payroll testing',
                    'status' => 'approved',
                    'approved_by' => $approver?->id,
                    'approved_at' => now(),
                ]
            );
        }

        if ($pattern === 'overtime' && $weekdays->last()) {
            $date = $weekdays->last()->toDateString();
            $overtime = Overtime::withTrashed()->updateOrCreate(
                ['employee_id' => $employee->id, 'date' => $date],
                [
                    'time_start' => '18:00',
                    'time_end' => '20:00',
                    'hours' => 2,
                    'reason' => 'Generated approved overtime for payroll testing',
                    'status' => 'approved',
                    'approved_by' => $approver?->id,
                    'approved_at' => Carbon::parse($date.' 20:00', $timezone)->utc(),
                ]
            );
            if ($overtime->trashed()) {
                $overtime->restore();
            }
        }
    }

    private function previousSemiMonthlyPeriod(): array
    {
        $today = now('Asia/Manila')->startOfDay();
        if ($today->day > 15) {
            $from = $today->copy()->startOfMonth();
            $to = $today->copy()->day(15);
        } else {
            $from = $today->copy()->subMonthNoOverflow()->day(16);
            $to = $from->copy()->endOfMonth();
        }

        return [$from, $to, $to->copy()->addDays(5)];
    }
}
