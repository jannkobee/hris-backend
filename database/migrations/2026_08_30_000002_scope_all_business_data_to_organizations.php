<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tenantTables = [
        'announcements', 'attendances', 'conversations', 'conversation_participants',
        'departments', 'employees', 'employee_addresses', 'employee_contacts',
        'employee_documents', 'employee_number_settings', 'employment_statuses',
        'holidays', 'job_grades', 'leave_types', 'leave_conversion_requests', 'leave_credits',
        'leave_credit_logs', 'leave_credit_settings', 'leave_requests',
        'leave_request_attachments', 'meeting_action_items', 'meeting_attachments',
        'meeting_attendees', 'meeting_rooms', 'messages', 'message_attachments',
        'overtimes', 'payroll_items', 'payroll_periods', 'positions', 'scheduled_tasks',
        'user_settings', 'workplace_meetings', 'audit_logs',
    ];

    public function up(): void
    {
        $legacyOrganizationId = DB::table('organizations')
            ->where('slug', config('tenancy.default_slug', 'legacy'))
            ->value('id');

        if (! $legacyOrganizationId) {
            throw new RuntimeException('The legacy organization must exist before tenant data can be scoped.');
        }

        foreach ($this->tenantTables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'organization_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignUuid('organization_id')->nullable()->after('id');
                $table->index('organization_id');
            });

            DB::table($tableName)->whereNull('organization_id')->update([
                'organization_id' => $legacyOrganizationId,
            ]);

            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tenantTables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'organization_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropForeign([$tableName === 'organizations' ? 'organization_id' : 'organization_id']);
                $table->dropIndex([$tableName.'_organization_id_index']);
                $table->dropColumn('organization_id');
            });
        }
    }
};
