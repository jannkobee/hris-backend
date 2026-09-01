<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
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
