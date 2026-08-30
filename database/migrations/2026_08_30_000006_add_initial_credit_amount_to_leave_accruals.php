<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL applies ALTER TABLE statements outside a transaction. These
        // guards make this migration safe to rerun after an interrupted deploy.
        if (! Schema::hasColumn('leave_credit_settings', 'initial_credit_amount')) {
            Schema::table('leave_credit_settings', function (Blueprint $table): void {
                $table->decimal('initial_credit_amount', 5, 2)
                    ->default(0)
                    ->after('grant_on_hire');
            });
        }

        // Preserve the previous Grant on Hire behaviour for existing rules.
        DB::table('leave_credit_settings')
            ->where('grant_on_hire', true)
            ->update(['initial_credit_amount' => DB::raw('credit_amount')]);

        if (! Schema::hasColumn('leave_credit_logs', 'accrual_type')) {
            Schema::table('leave_credit_logs', function (Blueprint $table): void {
                $table->string('accrual_type', 20)
                    ->default('recurring')
                    ->after('credited_amount');
            });
        }

        // The old index is the supporting index for leave_credit_setting_id's
        // foreign key. Add its replacement first so MySQL can safely remove it.
        if (! $this->indexExists('leave_credit_logs', 'leave_credit_logs_unique_accrual')) {
            Schema::table('leave_credit_logs', function (Blueprint $table): void {
                $table->unique(
                    ['leave_credit_setting_id', 'employee_id', 'year', 'month', 'accrual_type'],
                    'leave_credit_logs_unique_accrual'
                );
            });
        }

        if ($this->indexExists('leave_credit_logs', 'leave_credit_logs_unique_run')) {
            Schema::table('leave_credit_logs', function (Blueprint $table): void {
                $table->dropUnique('leave_credit_logs_unique_run');
            });
        }
    }

    public function down(): void
    {
        Schema::table('leave_credit_logs', function (Blueprint $table): void {
            $table->dropUnique('leave_credit_logs_unique_accrual');
            $table->dropColumn('accrual_type');
            $table->unique(
                ['leave_credit_setting_id', 'employee_id', 'year', 'month'],
                'leave_credit_logs_unique_run'
            );
        });

        Schema::table('leave_credit_settings', function (Blueprint $table): void {
            $table->dropColumn('initial_credit_amount');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn (object $item): bool => $item->name === $index);
        }

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
