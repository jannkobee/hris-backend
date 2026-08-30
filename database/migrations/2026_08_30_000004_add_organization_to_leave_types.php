<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leave_types') || Schema::hasColumn('leave_types', 'organization_id')) {
            return;
        }

        $legacyOrganizationId = DB::table('organizations')
            ->where('slug', config('tenancy.default_slug', 'legacy'))
            ->value('id');

        if (! $legacyOrganizationId) {
            throw new RuntimeException('The legacy organization must exist before leave types can be tenant-scoped.');
        }

        Schema::table('leave_types', function (Blueprint $table): void {
            $table->foreignUuid('organization_id')->nullable()->after('id');
            $table->index('organization_id');
        });

        DB::table('leave_types')->whereNull('organization_id')->update([
            'organization_id' => $legacyOrganizationId,
        ]);

        Schema::table('leave_types', function (Blueprint $table): void {
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leave_types') || ! Schema::hasColumn('leave_types', 'organization_id')) {
            return;
        }

        Schema::table('leave_types', function (Blueprint $table): void {
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id']);
            $table->dropColumn('organization_id');
        });
    }
};
