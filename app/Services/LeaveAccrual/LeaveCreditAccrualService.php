<?php

namespace App\Services\LeaveAccrual;

use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveCreditLog;
use App\Models\LeaveCreditSetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class LeaveCreditAccrualService
{
    /** @return array{credited: int, skipped: int, ineligible: int, failed: int} */
    public function accrueDueEmployees(int $month = null, int $year = null): array
    {
        $month ??= now()->month;
        $year ??= now()->year;
        $asOfDate = $this->asOfDate($month, $year);
        $result = $this->emptyResult();

        foreach ($this->dueSettings($month) as $setting) {
            $this->eligibleEmployees($setting, $asOfDate)
                ->chunkById(200, function ($employees) use ($setting, $month, $year, $asOfDate, &$result) {
                    foreach ($employees as $employee) {
                        $this->mergeResult($result, $this->accrueSettingForEmployee($employee, $setting, $month, $year, $asOfDate));
                    }
                });
        }

        return $result;
    }

    /** @return array{credited: int, skipped: int, ineligible: int, failed: int} */
    public function accrueEmployee(Employee $employee, int $month = null, int $year = null): array
    {
        $month ??= now()->month;
        $year ??= now()->year;
        $asOfDate = $this->asOfDate($month, $year);
        $employee->loadMissing('employmentStatus');
        $result = $this->emptyResult();

        foreach ($this->dueSettings($month) as $setting) {
            $this->mergeResult($result, $this->accrueSettingForEmployee($employee, $setting, $month, $year, $asOfDate));
        }

        return $result;
    }

    private function dueSettings(int $month)
    {
        return LeaveCreditSetting::query()
            ->where('is_active', true)
            ->whereJsonContains('run_months', $month)
            ->get();
    }

    private function eligibleEmployees(LeaveCreditSetting $setting, Carbon $asOfDate): Builder
    {
        $query = Employee::query()
            ->with('employmentStatus')
            ->whereNotNull('hire_date')
            ->where('hire_date', '<=', $asOfDate)
            ->where(function ($query) {
                $query->whereNull('employment_status_id')
                    ->orWhereHas('employmentStatus', fn ($status) => $status->whereRaw('LOWER(name) <> ?', ['separated']));
            });

        foreach ([
            'eligible_employment_status_ids' => 'employment_status_id',
            'eligible_department_ids' => 'department_id',
            'eligible_position_ids' => 'position_id',
            'eligible_job_grade_ids' => 'job_grade_id',
        ] as $settingColumn => $employeeColumn) {
            if (! empty($setting->{$settingColumn})) {
                $query->whereIn($employeeColumn, $setting->{$settingColumn});
            }
        }

        return $query;
    }

    /** @return array{credited: int, skipped: int, ineligible: int, failed: int} */
    private function accrueSettingForEmployee(Employee $employee, LeaveCreditSetting $setting, int $month, int $year, Carbon $asOfDate): array
    {
        if (! $this->isEligible($employee, $setting, $asOfDate)) {
            return ['credited' => 0, 'skipped' => 0, 'ineligible' => 1, 'failed' => 0];
        }

        try {
            DB::transaction(function () use ($employee, $setting, $month, $year) {
                LeaveCreditLog::create([
                    'leave_credit_setting_id' => $setting->id,
                    'employee_id' => $employee->id,
                    'leave_type_id' => $setting->leave_type_id,
                    'year' => $year,
                    'month' => $month,
                    'credited_amount' => $setting->credit_amount,
                ]);

                $ledger = LeaveCredit::query()->where([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $setting->leave_type_id,
                    'year' => $year,
                ])->lockForUpdate()->first();

                if (! $ledger) {
                    $ledger = new LeaveCredit([
                        'employee_id' => $employee->id,
                        'leave_type_id' => $setting->leave_type_id,
                        'year' => $year,
                        'used' => 0,
                        'total_earned' => $setting->credit_amount,
                    ]);
                    $ledger->save();

                    return;
                }

                $ledger->increment('total_earned', (float) $setting->credit_amount);
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return ['credited' => 0, 'skipped' => 1, 'ineligible' => 0, 'failed' => 0];
            }

            report($exception);

            return ['credited' => 0, 'skipped' => 0, 'ineligible' => 0, 'failed' => 1];
        }

        return ['credited' => 1, 'skipped' => 0, 'ineligible' => 0, 'failed' => 0];
    }

    private function isEligible(Employee $employee, LeaveCreditSetting $setting, Carbon $asOfDate): bool
    {
        if (! $employee->hire_date || $employee->hire_date->gt($asOfDate)) {
            return false;
        }

        if (strtolower((string) $employee->employmentStatus?->name) === 'separated') {
            return false;
        }

        if ($employee->hire_date->copy()->addMonthsNoOverflow($setting->minimum_service_months)->gt($asOfDate)) {
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

    private function asOfDate(int $month, int $year): Carbon
    {
        return $month === now()->month && $year === now()->year
            ? now()
            : Carbon::create($year, $month, 1)->endOfMonth();
    }

    /** @return array{credited: int, skipped: int, ineligible: int, failed: int} */
    private function emptyResult(): array
    {
        return ['credited' => 0, 'skipped' => 0, 'ineligible' => 0, 'failed' => 0];
    }

    private function mergeResult(array &$current, array $next): void
    {
        foreach ($current as $key => $value) {
            $current[$key] = $value + $next[$key];
        }
    }
}
