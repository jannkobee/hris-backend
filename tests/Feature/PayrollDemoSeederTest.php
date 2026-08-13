<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Services\Payroll\PayrollWorkSummaryService;
use Database\Seeders\PayrollDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_repeatable_payroll_scenarios_ready_for_generation(): void
    {
        $this->seed(PayrollDemoSeeder::class);
        $this->seed(PayrollDemoSeeder::class);

        $this->assertSame(6, Employee::query()->where('employee_no', 'like', 'PAY-DEMO-%')->count());
        $this->assertGreaterThan(20, Attendance::query()->count());
        $this->assertSame(1, LeaveRequest::query()->where('reason', 'like', 'Generated%')->count());
        $this->assertSame(1, Overtime::query()->where('reason', 'like', 'Generated%')->count());
        $this->assertSame(1, PayrollPeriod::query()->where('name', 'like', 'Demo Payroll%')->where('status', 'draft')->count());

        $lateEmployee = Employee::query()->where('employee_no', 'PAY-DEMO-002')->firstOrFail();
        $period = PayrollPeriod::query()->where('name', 'like', 'Demo Payroll%')->firstOrFail();
        $summary = app(PayrollWorkSummaryService::class)->summarize($lateEmployee, $period->date_from, $period->date_to);
        $this->assertGreaterThan(0, $summary['late_minutes']);

        $period->update(['status' => 'processed', 'total_gross' => 1000]);
        PayrollItem::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $lateEmployee->id,
            'calculation_snapshot' => [],
        ]);

        $this->seed(PayrollDemoSeeder::class);

        $period->refresh();
        $this->assertSame('draft', $period->status);
        $this->assertSame('0.00', $period->total_gross);
        $this->assertSame(0, $period->items()->count());
    }
}
