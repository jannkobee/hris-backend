<?php

use App\Models\Organization;
use App\Tenancy\TenantContext;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite has no enforced VARCHAR width and Laravel requires DBAL for
        // change(), so avoid an unnecessary schema alteration in tests.
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('employee_documents', function (Blueprint $table): void {
                $table->text('document_number')->nullable()->change();
            });
        }

        Organization::query()->each(function (Organization $organization): void {
            app(TenantContext::class)->run($organization, function () use ($organization): void {
                DB::table('employee_documents')
                    ->where('organization_id', $organization->id)
                    ->select(['id', 'document_number', 'notes'])
                    ->orderBy('id')
                    ->each(function (object $document) use ($organization): void {
                        $attributes = [];
                        if ($document->document_number !== null) {
                            $attributes['document_number'] = Crypt::encryptString($document->document_number);
                        }
                        if ($document->notes !== null) {
                            $attributes['notes'] = Crypt::encryptString($document->notes);
                        }
                        if ($attributes !== []) {
                            DB::table('employee_documents')
                                ->where('organization_id', $organization->id)
                                ->where('id', $document->id)
                                ->update($attributes);
                        }
                    });
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Encrypted ciphertext may exceed VARCHAR limits. This migration is
        // intentionally schema-forward-only; restore a backup to roll back.
    }
};
