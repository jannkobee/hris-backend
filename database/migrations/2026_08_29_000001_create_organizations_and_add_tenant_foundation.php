<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $legacyOrganizationId = (string) Str::uuid();
        $legacySlug = strtolower((string) config('tenancy.default_slug', 'legacy'));
        $legacyPlan = (string) config('tenancy.legacy_plan', config('plans.default', 'basic'));

        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $legacySlug)) {
            $legacySlug = 'legacy';
        }

        if (! in_array($legacyPlan, ['basic', 'enterprise'], true)) {
            $legacyPlan = 'basic';
        }

        Schema::create('organizations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('timezone')->default('UTC');
            $table->string('plan_code')->default('basic');
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->timestamps();
        });

        DB::table('organizations')->insert([
            'id' => $legacyOrganizationId,
            'slug' => $legacySlug,
            'name' => (string) config('tenancy.default_name', 'Legacy Organization'),
            'timezone' => (string) config('tenancy.default_timezone', 'UTC'),
            'plan_code' => $legacyPlan,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->addOrganizationColumn('roles');
        $this->addOrganizationColumn('users');
        $this->addOrganizationColumn('app_settings');

        DB::table('roles')->whereNull('organization_id')->update(['organization_id' => $legacyOrganizationId]);
        DB::table('users')->whereNull('organization_id')->update(['organization_id' => $legacyOrganizationId]);
        DB::table('app_settings')->whereNull('organization_id')->update(['organization_id' => $legacyOrganizationId]);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_email_unique');
            $table->unique(['organization_id', 'email'], 'users_organization_email_unique');
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->unique(['organization_id', 'name'], 'roles_organization_name_unique');
        });

        Schema::table('app_settings', function (Blueprint $table): void {
            $table->dropUnique('app_settings_key_unique');
            $table->unique(['organization_id', 'key'], 'app_settings_organization_key_unique');
        });

        $this->replaceRoleDeleteBehavior(restrict: true);
    }

    public function down(): void
    {
        $this->replaceRoleDeleteBehavior(restrict: false);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_organization_email_unique');
        });
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique('roles_organization_name_unique');
        });
        Schema::table('app_settings', function (Blueprint $table): void {
            $table->dropUnique('app_settings_organization_key_unique');
        });

        $this->dropOrganizationColumn('app_settings');
        $this->dropOrganizationColumn('users');
        $this->dropOrganizationColumn('roles');

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('email');
        });
        Schema::table('app_settings', function (Blueprint $table): void {
            $table->unique('key');
        });

        Schema::dropIfExists('organizations');
    }

    private function addOrganizationColumn(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table): void {
            $table->foreignUuid('organization_id')
                ->nullable()
                ->after('id')
                ->constrained('organizations')
                ->restrictOnDelete();
            $table->index('organization_id');
        });
    }

    private function dropOrganizationColumn(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign($tableName.'_organization_id_foreign');
            }

            $table->dropIndex($tableName.'_organization_id_index');
            $table->dropColumn('organization_id');
        });
    }

    private function replaceRoleDeleteBehavior(bool $restrict): void
    {
        // SQLite cannot add a foreign key to an existing table. Its test
        // schema did not receive the original post-create users.role_id key
        // either, so there is nothing to replace for that driver.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($restrict): void {
            $table->dropForeign('users_role_id_foreign');

            $foreign = $table->foreign('role_id', 'users_role_id_foreign')
                ->references('id')
                ->on('roles');

            if ($restrict) {
                $foreign->restrictOnDelete();
            } else {
                $foreign->cascadeOnDelete();
            }
        });
    }
};
