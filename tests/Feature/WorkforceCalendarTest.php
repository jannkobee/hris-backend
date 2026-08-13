<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkforceCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_and_manage_permissions_are_enforced_and_dates_are_unique(): void
    {
        $role = Role::create(['name' => 'Calendar Viewer']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $role->permissions()->attach($this->permission('view-holidays'));

        $this->actingAs($user, 'sanctum')
            ->getJson(route('holidays.index'))
            ->assertOk();

        $payload = [
            'name' => 'Company Foundation Day',
            'date' => '2026-09-01',
            'type' => 'company_holiday',
            'description' => 'Company-wide holiday.',
        ];

        $this->actingAs($user, 'sanctum')
            ->postJson(route('holidays.store'), $payload)
            ->assertForbidden();

        $role->permissions()->attach($this->permission('manage-holidays'));
        $user->unsetRelation('role');

        $this->actingAs($user, 'sanctum')
            ->postJson(route('holidays.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.date', '2026-09-01');

        $this->actingAs($user, 'sanctum')
            ->postJson(route('holidays.store'), [
                ...$payload,
                'name' => 'Conflicting calendar entry',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date');
    }

    public function test_calendar_can_be_filtered_by_year_and_day_type(): void
    {
        $role = Role::create(['name' => 'Calendar Viewer']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $role->permissions()->attach($this->permission('view-holidays'));

        \App\Models\Holiday::create([
            'name' => 'Regular Holiday',
            'date' => '2026-12-25',
            'type' => 'regular_holiday',
        ]);
        \App\Models\Holiday::create([
            'name' => 'Working Exception',
            'date' => '2027-01-02',
            'type' => 'special_working_day',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson(route('holidays.index', [
                'year' => 2027,
                'type' => 'special_working_day',
                'all' => 1,
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Working Exception');
    }

    private function permission(string $slug): Permission
    {
        return Permission::create([
            'model' => 'Organization Setup',
            'name' => $slug,
            'slug' => $slug,
            'description' => "Test permission for {$slug}",
        ]);
    }
}
