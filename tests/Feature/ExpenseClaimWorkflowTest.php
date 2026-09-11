<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseClaimWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_claim_is_reviewed_and_reimbursed_by_authorized_users(): void
    {
        $employeeUser = $this->userWithRole('User');
        $employee = Employee::create(['user_id' => $employeeUser->id, 'employee_no' => 'EXP-001']);
        $manager = $this->userWithRole('Admin');
        $finance = $this->userWithRole('Admin');
        $this->grant($manager, 'manage-employees');
        $this->grant($finance, 'manage-payroll');

        $claimId = $this->actingAs($employeeUser, 'sanctum')->postJson(route('expense-claims.store'), [
            'employee_id' => $employee->id,
            'expense_date' => now()->toDateString(),
            'category' => 'Travel',
            'description' => 'Client site visit',
            'amount' => 1250.50,
        ])->assertCreated()->json('data.id');

        $this->actingAs($employeeUser, 'sanctum')
            ->postJson(route('expense-claims.reimburse', $claimId), ['payment_reference' => 'PAY-001'])
            ->assertForbidden();

        $this->actingAs($manager, 'sanctum')
            ->postJson(route('expense-claims.review', $claimId), ['status' => 'approved', 'reviewer_note' => 'Approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->actingAs($finance, 'sanctum')
            ->postJson(route('expense-claims.reimburse', $claimId), ['payment_reference' => 'PAY-001'])
            ->assertOk()
            ->assertJsonPath('data.status', 'reimbursed');
    }

    public function test_employees_can_list_only_their_own_claims(): void
    {
        $owner = $this->userWithRole('Expense employee');
        $colleague = $this->userWithRole('Expense employee');
        $ownClaim = $this->claimFor($owner, 'OWN-CLAIM');
        $this->claimFor($colleague, 'COLLEAGUE-CLAIM');

        $this->actingAs($owner, 'sanctum')->getJson(route('expense-claims.index'))
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownClaim->id)
            ->assertDontSee('COLLEAGUE-CLAIM');

        $this->actingAs($owner, 'sanctum')->getJson(route('expense-claims.index', ['employee_id' => $colleague->employee->id]))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ownClaim->id);
    }

    public function test_account_without_an_employee_gets_an_empty_claim_list(): void
    {
        $this->claimFor($this->userWithRole('Expense employee'), 'OTHER-CLAIM');
        $unlinked = $this->userWithRole('Unlinked expense employee');
        $this->actingAs($unlinked, 'sanctum')->getJson(route('expense-claims.index'))
            ->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_hr_and_finance_can_list_tenant_claims_but_never_other_tenants(): void
    {
        $localClaim = $this->claimFor($this->userWithRole('Expense employee'), 'LOCAL-CLAIM');
        $foreign = Organization::create([
            'slug' => 'expense-other', 'name' => 'Other company', 'timezone' => 'Asia/Manila',
            'country_code' => 'PH', 'plan_code' => Organization::PLAN_ENTERPRISE,
            'status' => Organization::STATUS_ACTIVE,
        ]);
        app(TenantContext::class)->run($foreign, function (): void {
            $this->claimFor($this->userWithRole('Foreign employee'), 'FOREIGN-CLAIM');
        });

        foreach (['view-employees', 'manage-employees', 'manage-payroll'] as $permission) {
            $reviewer = $this->userWithRole('Expense '.$permission);
            $this->grant($reviewer, $permission);
            $this->actingAs($reviewer, 'sanctum')->getJson(route('expense-claims.index'))
                ->assertOk()->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $localClaim->id)->assertDontSee('FOREIGN-CLAIM');
        }
    }

    public function test_self_service_listing_does_not_grant_review_or_payment_permissions(): void
    {
        $owner = $this->userWithRole('Expense employee');
        $claim = $this->claimFor($owner, 'OWN-CLAIM');
        $this->actingAs($owner, 'sanctum')->postJson(route('expense-claims.review', $claim), ['status' => 'approved'])->assertForbidden();
        $this->postJson(route('expense-claims.reimburse', $claim), ['payment_reference' => 'UNAUTHORIZED'])->assertForbidden();
        $this->assertSame('submitted', $claim->fresh()->status);
    }

    public function test_expense_list_requires_authentication(): void
    {
        $this->getJson(route('expense-claims.index'))->assertUnauthorized();
    }

    private function claimFor(User $user, string $description): ExpenseClaim
    {
        $employee = Employee::create(['user_id' => $user->id, 'employee_no' => $description]);

        return ExpenseClaim::create([
            'employee_id' => $employee->id, 'expense_date' => now()->toDateString(),
            'category' => 'Travel', 'description' => $description, 'amount' => 1250.50, 'status' => 'submitted',
        ]);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function grant(User $user, string $slug): void
    {
        $permission = Permission::create(['model' => 'test', 'name' => $slug, 'slug' => $slug]);
        $user->role->permissions()->syncWithoutDetaching([$permission->id]);
    }
}
