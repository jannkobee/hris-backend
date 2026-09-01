<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Role;
use App\Models\ShiftAssignment;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_create_templates_and_assign_a_snapshot_to_an_employee(): void
    {
        $role = Role::create(['name' => 'Admin']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $employee = Employee::create(['user_id' => $user->id, 'employee_no' => 'SHIFT-001']);

        $template = $this->actingAs($user, 'sanctum')->postJson(route('shift-templates.store'), [
            'name' => 'Day Shift',
            'code' => 'DAY',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'break_minutes' => 60,
            'grace_minutes' => 10,
            'days_of_week' => [1, 2, 3, 4, 5],
        ])->assertCreated()->json('data');

        $assignment = $this->actingAs($user, 'sanctum')->postJson(route('shift-assignments.store'), [
            'employee_id' => $employee->id,
            'shift_template_id' => $template['id'],
            'work_date' => '2026-09-01',
            'notes' => 'Opening roster',
        ])->assertCreated()->json('data');

        $this->assertSame('Day Shift', $assignment['shift_name']);
        $this->assertSame('08:00', $assignment['start_time']);
        $this->assertSame(60, $assignment['break_minutes']);

        ShiftTemplate::query()->findOrFail($template['id'])->update(['start_time' => '09:00']);

        $this->assertSame('08:00', ShiftAssignment::query()->findOrFail($assignment['id'])->start_time);
    }

    public function test_a_shift_template_from_another_organization_cannot_be_assigned(): void
    {
        $context = app(TenantContext::class);
        $alpha = $context->organization();
        $role = Role::create(['name' => 'Admin']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $employee = Employee::create(['user_id' => $user->id, 'employee_no' => 'SHIFT-ALPHA']);

        $beta = Organization::create([
            'slug' => 'shift-beta', 'name' => 'Shift Beta', 'timezone' => 'Asia/Manila',
            'plan_code' => Organization::PLAN_ENTERPRISE, 'status' => Organization::STATUS_ACTIVE,
        ]);
        $context->set($beta);
        $foreignTemplate = ShiftTemplate::create([
            'name' => 'Foreign Shift', 'start_time' => '08:00', 'end_time' => '17:00',
        ]);
        $context->set($alpha);

        $this->actingAs($user, 'sanctum')->postJson(route('shift-assignments.store'), [
            'employee_id' => $employee->id,
            'shift_template_id' => $foreignTemplate->id,
            'work_date' => '2026-09-01',
        ])->assertUnprocessable()->assertJsonValidationErrors('shift_template_id');
    }
}
