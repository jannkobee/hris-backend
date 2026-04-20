<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\EmployeeNumber\EmployeeNumberServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReformatEmployeeNumbers extends Command
{
    // The command you will type in the terminal
    protected $signature = 'employee:reformat-numbers';

    protected $description = 'Retroactively generates new employee numbers for all existing employees based on current settings.';

    public function handle(EmployeeNumberServiceInterface $employeeNumberService)
    {
        $this->warn('WARNING: This will overwrite ALL existing employee numbers.');
        if (!$this->confirm('Are you sure you want to proceed?')) {
            $this->info('Operation cancelled.');
            return;
        }

        // Fetch all employees (oldest first so incrementing makes sense)
        $employees = Employee::orderBy('created_at', 'asc')->get();
        $bar = $this->output->createProgressBar(count($employees));

        DB::beginTransaction();
        try {
            foreach ($employees as $employee) {
                // Generate a new number using your current active strategy
                $newNumber = $employeeNumberService->generate();

                // Save it without triggering model events (to prevent updating the 'updated_at' timestamp)
                $employee->employee_no = $newNumber;
                $employee->saveQuietly();

                $bar->advance();
            }

            DB::commit();
            $bar->finish();

            $this->newLine();
            $this->info('Successfully updated ' . count($employees) . ' employee numbers!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('An error occurred. No numbers were changed.');
            $this->error($e->getMessage());
        }
    }
}
