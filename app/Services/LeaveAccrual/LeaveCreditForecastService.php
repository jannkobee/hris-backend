<?php

namespace App\Services\LeaveAccrual;

use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveCreditSetting;
use App\Models\LeaveType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveCreditForecastService
{
    /**
     * Forecast leave credit balances from today until a target date.
     *
     * @param Employee $employee
     * @param Carbon $targetDate
     * @param string|null $leaveTypeId
     * @return array
     */
    public function forecast(Employee $employee, Carbon $targetDate, ?string $leaveTypeId = null): array
    {
        $employee->loadMissing(['employmentStatus', 'department', 'position', 'jobGrade', 'user']);

        $currentYear = now()->year;

        // Fetch current active leave credits for current year
        $creditQuery = LeaveCredit::query()
            ->with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $currentYear);

        if ($leaveTypeId) {
            $creditQuery->where('leave_type_id', $leaveTypeId);
        }

        $currentCredits = $creditQuery->get()->keyBy('leave_type_id');

        // Fetch active settings
        $settingsQuery = LeaveCreditSetting::query()
            ->with('leaveType')
            ->where('is_active', true);

        if ($leaveTypeId) {
            $settingsQuery->where('leave_type_id', $leaveTypeId);
        }

        $settings = $settingsQuery->get();

        // Get unique leave types involved
        $leaveTypes = LeaveType::query();
        if ($leaveTypeId) {
            $leaveTypes->where('id', $leaveTypeId);
        } else {
            $involvedTypeIds = $currentCredits->keys()->merge($settings->pluck('leave_type_id'))->unique()->filter();
            if ($involvedTypeIds->isNotEmpty()) {
                $leaveTypes->whereIn('id', $involvedTypeIds);
            }
        }
        $allLeaveTypes = $leaveTypes->get()->keyBy('id');

        $forecasts = [];

        foreach ($allLeaveTypes as $typeId => $leaveType) {
            $currentLedger = $currentCredits->get($typeId);
            $currentBalance = $currentLedger ? (float) $currentLedger->remaining : 0.0;
            $typeSettings = $settings->where('leave_type_id', $typeId);

            $monthlySchedule = [];
            $projectedAccruals = 0.0;
            $runningBalance = $currentBalance;

            // Iterate months from next month up to target date's month
            $start = now()->copy()->startOfMonth()->addMonth();
            $end = $targetDate->copy()->startOfMonth();

            if ($start->lte($end)) {
                $period = CarbonPeriod::create($start, '1 month', $end);

                foreach ($period as $monthDate) {
                    $monthNum = (int) $monthDate->month;
                    $asOf = $monthDate->copy()->endOfMonth();
                    $monthAccrual = 0.0;

                    foreach ($typeSettings as $setting) {
                        $runMonths = $setting->run_months ?? [];
                        if (in_array($monthNum, $runMonths, true) && $this->isEligible($employee, $setting, $asOf)) {
                            $monthAccrual += (float) $setting->credit_amount;
                        }
                    }

                    if ($monthAccrual > 0) {
                        $projectedAccruals += $monthAccrual;
                        $runningBalance += $monthAccrual;
                        $monthlySchedule[] = [
                            'period' => $monthDate->format('Y-m'),
                            'month_name' => $monthDate->format('F Y'),
                            'accrued_amount' => round($monthAccrual, 2),
                            'projected_balance' => round($runningBalance, 2),
                        ];
                    }
                }
            }

            $forecasts[] = [
                'leave_type_id' => $typeId,
                'leave_type_name' => $leaveType->name,
                'leave_type_code' => $leaveType->code ?? null,
                'is_paid' => (bool) $leaveType->is_paid,
                'current_balance' => round($currentBalance, 2),
                'projected_accruals' => round($projectedAccruals, 2),
                'projected_balance' => round($runningBalance, 2),
                'schedule' => $monthlySchedule,
            ];
        }

        return [
            'employee' => [
                'id' => $employee->id,
                'employee_no' => $employee->employee_no,
                'name' => $employee->user ? trim(($employee->user->first_name ?? '') . ' ' . ($employee->user->last_name ?? '')) : 'Employee',
            ],
            'as_of_date' => now()->toDateString(),
            'target_date' => $targetDate->toDateString(),
            'forecasts' => $forecasts,
        ];
    }

    private function isEligible(Employee $employee, LeaveCreditSetting $setting, Carbon $asOfDate): bool
    {
        if (! $employee->hire_date || $employee->hire_date->gt($asOfDate)) {
            return false;
        }

        if (strtolower((string) $employee->employmentStatus?->name) === 'separated') {
            return false;
        }

        if ($employee->hire_date->copy()->addMonthsNoOverflow((int) $setting->minimum_service_months)->gt($asOfDate)) {
            return false;
        }

        foreach ([
            'eligible_employment_status_ids' => 'employment_status_id',
            'eligible_department_ids' => 'department_id',
            'eligible_position_ids' => 'position_id',
            'eligible_job_grade_ids' => 'job_grade_id',
        ] as $settingColumn => $employeeColumn) {
            if (! empty($setting->{$settingColumn}) && ! in_array($employee->{$employeeColumn}, $setting->{$settingColumn}, true)) {
                return false;
            }
        }

        return true;
    }
}

