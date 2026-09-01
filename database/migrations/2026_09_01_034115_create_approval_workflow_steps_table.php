<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('approval_workflow_steps')) {
            return;
        }

        Schema::create('approval_workflow_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('workflow_id')->index();
            $table->unsignedSmallInteger('sequence');
            $table->string('approver_type');
            $table->uuid('approver_id')->nullable();
            $table->json('conditions')->nullable();
            $table->unsignedInteger('sla_hours')->nullable();
            $table->timestamps();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('workflow_id', 'approval_workflow_step_wf_fk')->references('id')->on('approval_workflows')->cascadeOnDelete();
            $table->unique(['organization_id', 'workflow_id', 'sequence'], 'approval_wf_step_sequence_uq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_workflow_steps');
    }
};
