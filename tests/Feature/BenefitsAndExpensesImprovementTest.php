<?php

namespace Tests\Feature;

use App\Models\BenefitEnrollment;
use App\Models\BenefitPlan;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BenefitsAndExpensesImprovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_claim_with_receipt_upload_is_stored_privately_and_accessible_by_owner(): void
    {
        Storage::fake();

        $employeeUser = $this->userWithRole('Employee');
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_no' => 'EXP-REC-01',
        ]);

        $receiptFile = UploadedFile::fake()->create('hotel_receipt.pdf', 500, 'application/pdf');

        $response = $this->actingAs($employeeUser, 'sanctum')->postJson(route('expense-claims.store'), [
            'employee_id' => $employee->id,
            'expense_date' => now()->toDateString(),
            'category' => 'Travel',
            'description' => 'Hotel stay for project launch',
            'amount' => 3500.00,
            'receipt' => $receiptFile,
        ]);

        $response->assertCreated();
        $claimId = $response->json('data.id');
        $this->assertTrue($response->json('data.has_receipt'));

        $claim = ExpenseClaim::findOrFail($claimId);
        $this->assertNotNull($claim->receipt_path);
        Storage::disk(config('filesystems.default'))->assertExists($claim->receipt_path);

        // Owner can access the receipt stream
        $receiptResponse = $this->actingAs($employeeUser, 'sanctum')->get(route('expense-claims.receipt', $claimId));
        $receiptResponse->assertOk();
        $this->assertSame('application/pdf', $receiptResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $receiptResponse->headers->get('Content-Disposition'));
    }

    public function test_receipt_download_authorization_blocks_unauthorized_colleagues_and_allows_hr_and_payroll(): void
    {
        Storage::fake();

        $ownerUser = $this->userWithRole('Employee');
        $owner = Employee::create(['user_id' => $ownerUser->id, 'employee_no' => 'OWN-01']);

        $colleagueUser = $this->userWithRole('Employee');
        Employee::create(['user_id' => $colleagueUser->id, 'employee_no' => 'COL-01']);

        $hrUser = $this->userWithRole('HR');
        $this->grant($hrUser, 'manage-employees');

        $payrollUser = $this->userWithRole('Payroll');
        $this->grant($payrollUser, 'manage-payroll');

        $receiptFile = UploadedFile::fake()->image('meal_receipt.png', 400, 300);

        $claimId = $this->actingAs($ownerUser, 'sanctum')->postJson(route('expense-claims.store'), [
            'employee_id' => $owner->id,
            'expense_date' => now()->toDateString(),
            'category' => 'Meals',
            'description' => 'Team lunch',
            'amount' => 1200.00,
            'receipt' => $receiptFile,
        ])->assertCreated()->json('data.id');

        // Other colleague receives 403 Forbidden
        $this->actingAs($colleagueUser, 'sanctum')->get(route('expense-claims.receipt', $claimId))
            ->assertForbidden();

        // HR can view receipt
        $this->actingAs($hrUser, 'sanctum')->get(route('expense-claims.receipt', $claimId))
            ->assertOk();

        // Payroll can view receipt
        $this->actingAs($payrollUser, 'sanctum')->get(route('expense-claims.receipt', $claimId))
            ->assertOk();
    }

    public function test_receipt_download_is_isolated_by_tenant(): void
    {
        Storage::fake();

        $foreignOrg = Organization::create([
            'slug' => 'foreign-company',
            'name' => 'Foreign Company',
            'timezone' => 'Asia/Manila',
            'country_code' => 'PH',
            'plan_code' => Organization::PLAN_ENTERPRISE,
            'status' => Organization::STATUS_ACTIVE,
        ]);

        $foreignClaimId = null;
        app(TenantContext::class)->run($foreignOrg, function () use (&$foreignClaimId): void {
            $foreignUser = $this->userWithRole('Foreign employee');
            $foreignEmp = Employee::create(['user_id' => $foreignUser->id, 'employee_no' => 'FOR-01']);
            $receipt = UploadedFile::fake()->create('foreign.pdf', 100, 'application/pdf');
            $claim = ExpenseClaim::create([
                'employee_id' => $foreignEmp->id,
                'expense_date' => now()->toDateString(),
                'category' => 'Travel',
                'description' => 'Foreign travel',
                'amount' => 500,
                'status' => 'submitted',
                'receipt_path' => $receipt->store("organizations/{$foreignEmp->organization_id}/expense-receipts"),
            ]);
            $foreignClaimId = $claim->id;
        });

        $localHr = $this->userWithRole('Local HR');
        $this->grant($localHr, 'manage-employees');

        // Local HR cannot access foreign claim's receipt because it's isolated by tenant
        $this->actingAs($localHr, 'sanctum')->getJson(route('expense-claims.receipt', $foreignClaimId))
            ->assertNotFound();
    }

    public function test_expense_claim_csv_export_requires_permission_and_streams_tenant_claims(): void
    {
        $employeeUser = $this->userWithRole('Employee');
        $employee = Employee::create(['user_id' => $employeeUser->id, 'employee_no' => 'EXP-CSV-01']);

        ExpenseClaim::create([
            'employee_id' => $employee->id,
            'expense_date' => now()->toDateString(),
            'category' => 'Supplies',
            'description' => 'Office stationery',
            'amount' => 850.50,
            'status' => 'submitted',
        ]);

        // Regular employee without permission receives 403
        $this->actingAs($employeeUser, 'sanctum')->get(route('expense-claims.export'))
            ->assertForbidden();

        // HR manager can export
        $hrUser = $this->userWithRole('HR');
        $this->grant($hrUser, 'manage-employees');

        $response = $this->actingAs($hrUser, 'sanctum')->get(route('expense-claims.export'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Office stationery', $response->streamedContent());
        $this->assertStringContainsString('850.50', $response->streamedContent());
    }

    public function test_benefit_plan_can_be_updated_and_deactivated_by_hr(): void
    {
        $hrUser = $this->userWithRole('HR');
        $this->grant($hrUser, 'manage-employees');

        $createResponse = $this->actingAs($hrUser, 'sanctum')->postJson(route('benefit-plans.store'), [
            'name' => 'Health HMO Plan',
            'description' => 'Comprehensive healthcare',
            'employee_contribution' => 500.00,
            'employer_contribution' => 1500.00,
            'is_active' => true,
        ]);
        $createResponse->assertCreated();
        $planId = $createResponse->json('data.id');

        // Update plan details
        $updateResponse = $this->actingAs($hrUser, 'sanctum')->patchJson(route('benefit-plans.update', $planId), [
            'employee_contribution' => 600.00,
            'employer_contribution' => 1800.00,
            'is_active' => false,
        ]);
        $updateResponse->assertOk();
        $this->assertSame('600.00', $updateResponse->json('data.employee_contribution'));
        $this->assertFalse($updateResponse->json('data.is_active'));

        // Non-HR receives 403
        $regularUser = $this->userWithRole('Employee');
        $this->actingAs($regularUser, 'sanctum')->patchJson(route('benefit-plans.update', $planId), [
            'name' => 'Hacked Plan',
        ])->assertForbidden();
    }

    public function test_benefit_plan_enrollments_roster_can_be_retrieved_and_cancelled(): void
    {
        $hrUser = $this->userWithRole('HR');
        $this->grant($hrUser, 'manage-employees');

        $employeeUser = $this->userWithRole('Employee');
        $employee = Employee::create(['user_id' => $employeeUser->id, 'employee_no' => 'BEN-EMP-01']);

        $plan = BenefitPlan::create([
            'name' => 'Dental Care',
            'employee_contribution' => 100.00,
            'employer_contribution' => 200.00,
            'is_active' => true,
        ]);

        $enrollmentResponse = $this->actingAs($hrUser, 'sanctum')->postJson(route('benefit-plans.enrollments.store', $plan->id), [
            'employee_id' => $employee->id,
            'effective_from' => now()->toDateString(),
        ]);
        $enrollmentResponse->assertCreated();
        $enrollmentId = $enrollmentResponse->json('data.id');

        // List enrollments for the plan
        $rosterResponse = $this->actingAs($hrUser, 'sanctum')->getJson(route('benefit-plans.enrollments.index', $plan->id));
        $rosterResponse->assertOk();
        $rosterResponse->assertJsonCount(1, 'data');
        $this->assertSame($enrollmentId, $rosterResponse->json('data.0.id'));

        // Cancel the enrollment
        $cancelResponse = $this->actingAs($hrUser, 'sanctum')->deleteJson(route('benefit-enrollments.destroy', $enrollmentId));
        $cancelResponse->assertOk();
        $this->assertSame('cancelled', $cancelResponse->json('data.status'));
        $this->assertSame('cancelled', BenefitEnrollment::findOrFail($enrollmentId)->status);
    }

    public function test_philippine_de_minimis_ceilings_endpoint_returns_statutory_thresholds(): void
    {
        $user = $this->userWithRole('Employee');
        $response = $this->actingAs($user, 'sanctum')->getJson(route('benefit-plans.de-minimis-ceilings'));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'rice_subsidy' => ['name', 'ceiling_amount', 'frequency', 'annual_ceiling', 'description', 'statutory_reference'],
                'uniform_clothing' => ['name', 'ceiling_amount', 'frequency', 'annual_ceiling', 'description', 'statutory_reference'],
                'medical_cash_allowance',
                'laundry_allowance',
            ],
        ]);
        $this->assertEquals(2000, $response->json('data.rice_subsidy.ceiling_amount'));
        $this->assertEquals(6000, $response->json('data.uniform_clothing.ceiling_amount'));
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function grant(User $user, string $slug): void
    {
        $permission = Permission::firstOrCreate(['model' => 'test', 'name' => $slug, 'slug' => $slug]);
        $user->role->permissions()->syncWithoutDetaching([$permission->id]);
    }
}
