<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\BenefitEnrollment;
use App\Models\BenefitPlan;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\Overtime;
use App\Models\PerformanceGoal;
use App\Models\PerformanceReview;
use App\Models\Position;
use App\Models\TrainingCourse;
use App\Models\TrainingEnrollment;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SeedIndustryReadinessDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'industry:seed-demo {organization : Organization slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed idempotent test data for the industry-readiness workflows in one organization.';

    /**
     * Execute the console command.
     */
    public function handle(TenantContext $tenantContext): int
    {
        $organization = Organization::query()->where('slug', $this->argument('organization'))->first();
        if (! $organization) {
            $this->error('Organization not found.');

            return self::FAILURE;
        }
        $tables = ['performance_goals', 'performance_reviews', 'training_courses', 'training_enrollments', 'benefit_plans', 'benefit_enrollments', 'expense_claims'];
        if (collect($tables)->contains(fn (string $table) => ! Schema::hasTable($table))) {
            $this->error('Run php artisan migrate first; one or more industry-readiness tables are missing.');

            return self::FAILURE;
        }

        $tenantContext->run($organization, function (): void {
            $user = User::query()->firstOrFail();
            $department = Department::query()->firstOrCreate(['name' => 'People Operations'], ['description' => 'Demo department']);
            $position = Position::query()->firstOrCreate(['name' => 'HR Specialist'], ['department_id' => $department->getKey(), 'description' => 'Demo position']);
            $employee = Employee::query()->firstOrCreate(['user_id' => $user->getKey()], ['employee_no' => 'DEMO-001', 'department_id' => $department->getKey(), 'position_id' => $position->getKey(), 'hire_date' => now()->subYear()->toDateString()]);
            PerformanceGoal::query()->firstOrCreate(['employee_id' => $employee->getKey(), 'title' => 'Complete HRIS rollout'], ['owner_id' => $user->getKey(), 'progress' => 55, 'status' => 'active', 'due_date' => now()->addMonths(2)->toDateString()]);
            PerformanceReview::query()->firstOrCreate(['employee_id' => $employee->getKey(), 'cycle_name' => now()->year.' Midyear Review'], ['reviewer_id' => $user->getKey(), 'rating' => 4, 'feedback' => 'Demonstrates consistent delivery.', 'status' => 'completed']);
            $course = TrainingCourse::query()->firstOrCreate(['name' => 'Data Privacy Fundamentals'], ['description' => 'Required annual privacy training', 'requires_certificate' => true]);
            TrainingEnrollment::query()->firstOrCreate(['course_id' => $course->getKey(), 'employee_id' => $employee->getKey()], ['status' => 'completed', 'completed_on' => now()->subMonth()->toDateString(), 'certificate_expires_on' => now()->addYear()->toDateString()]);
            $plan = BenefitPlan::query()->firstOrCreate(['name' => 'Health Coverage'], ['employee_contribution' => 500, 'employer_contribution' => 1500, 'is_active' => true]);
            BenefitEnrollment::query()->firstOrCreate(['benefit_plan_id' => $plan->getKey(), 'employee_id' => $employee->getKey()], ['effective_from' => now()->startOfYear()->toDateString(), 'status' => 'active']);
            ExpenseClaim::query()->firstOrCreate(['employee_id' => $employee->getKey(), 'description' => 'Client travel'], ['expense_date' => now()->subDays(7)->toDateString(), 'category' => 'Travel', 'amount' => 1250, 'status' => 'submitted']);
            ExpenseClaim::query()->firstOrCreate(['employee_id' => $employee->getKey(), 'description' => 'Office supplies'], ['expense_date' => now()->subDays(14)->toDateString(), 'category' => 'Supplies', 'amount' => 840, 'status' => 'reimbursed', 'reviewed_by' => $user->getKey(), 'reviewed_at' => now()->subDays(12), 'reimbursed_by' => $user->getKey(), 'reimbursed_at' => now()->subDays(10), 'payment_reference' => 'DEMO-EXP-001']);
            $leaveType = LeaveType::query()->firstOrCreate(['name' => 'Vacation Leave'], ['default_days' => 15, 'is_paid' => true]);
            LeaveRequest::query()->firstOrCreate(['employee_id' => $employee->getKey(), 'leave_type_id' => $leaveType->getKey(), 'start_date' => now()->addWeeks(2)->toDateString()], ['start_time' => '09:00', 'end_date' => now()->addWeeks(2)->toDateString(), 'end_time' => '18:00', 'reason' => 'Family vacation', 'status' => 'pending']);
            Overtime::query()->firstOrCreate(['employee_id' => $employee->getKey(), 'date' => now()->subDays(3)->toDateString()], ['time_start' => '18:00', 'time_end' => '21:00', 'hours' => 3, 'day_type' => 'regular_working_day', 'premium_multiplier' => 1.25, 'premium_hours' => 3.75, 'reason' => 'Month-end support', 'status' => 'approved', 'approved_by' => $user->getKey(), 'approved_at' => now()->subDays(2)]);
            foreach (range(1, 5) as $offset) {
                $date = now()->subDays($offset)->startOfDay();
                if ($date->isWeekend()) {
                    continue;
                }
                Attendance::query()->firstOrCreate(['employee_id' => $employee->getKey(), 'date' => $date->toDateString()], ['time_in' => $date->copy()->setTime(1, 5), 'time_out' => $date->copy()->setTime(10, 0), 'late_minutes' => $offset === 1 ? 5 : 0]);
            }
            Holiday::query()->firstOrCreate(['date' => now()->addMonth()->startOfMonth()->toDateString()], ['name' => 'HRISFlow Company Day', 'type' => 'company_holiday', 'description' => 'Demo workforce-calendar event']);
            Announcement::query()->firstOrCreate(['title' => 'Welcome to your HRISFlow demo'], ['content' => 'Explore attendance, leave, overtime, benefits, expenses, training, and performance workflows with these sample records.', 'published_at' => now()->toDateString(), 'is_active' => true, 'created_by' => $user->getKey()]);
        });
        $this->info('Industry-readiness demo data is ready.');

        return self::SUCCESS;
    }
}
