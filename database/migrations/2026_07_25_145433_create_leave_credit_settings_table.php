<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_credit_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('leave_type_id')->constrained('leave_types')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->decimal('credit_amount', 5, 2)->default(0);

            // Label only, e.g. monthly / quarterly / semi_annually / annually / custom.
            // The actual schedule is driven by run_months below.
            $table->string('frequency')->default('monthly');

            // Calendar months (1-12) this setting should fire on, e.g. [1,2,3,...,12]
            // for monthly, [1,4,7,10] for quarterly, [6] for annually in June, etc.
            $table->json('run_months');

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_credit_settings');
    }
};
