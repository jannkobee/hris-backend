<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkplaceMeeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_a_stable_daily_message_and_only_the_users_calendar_records(): void
    {
        $user = $this->workplaceUser('Employee');
        $otherUser = $this->workplaceUser('Other employee');
        $employee = Employee::create(['user_id' => $user->id, 'employee_no' => 'EMP-CALENDAR']);
        $leaveType = LeaveType::create(['name' => 'Vacation leave', 'default_days' => 10, 'is_paid' => true]);
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-17',
            'start_time' => '09:00',
            'end_date' => '2026-08-18',
            'end_time' => '18:00',
            'reason' => 'Vacation',
            'status' => 'approved',
        ]);

        $meeting = WorkplaceMeeting::create([
            'organizer_id' => $otherUser->id,
            'title' => 'Team planning',
            'type' => 'planning',
            'starts_at' => '2026-08-19 01:00:00',
            'ends_at' => '2026-08-19 02:00:00',
            'status' => 'scheduled',
        ]);
        $meeting->attendees()->attach($user->id);
        WorkplaceMeeting::create([
            'organizer_id' => $otherUser->id,
            'title' => 'Private meeting',
            'type' => 'one_on_one',
            'starts_at' => '2026-08-20 01:00:00',
            'ends_at' => '2026-08-20 02:00:00',
            'status' => 'scheduled',
        ]);
        Announcement::create([
            'title' => 'Company town hall',
            'content' => 'Town hall details',
            'published_at' => '2026-08-21',
            'is_active' => true,
            'created_by' => $otherUser->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson(route('dashboard.overview', [
            'from' => '2026-08-01',
            'to' => '2026-08-31',
        ]))->assertOk();

        $events = collect($response->json('data.events'));
        $this->assertSame(1, $events->where('type', 'meeting')->count());
        $this->assertSame(2, $events->where('type', 'leave')->count());
        $this->assertSame(1, $events->where('type', 'announcement')->count());
        $this->assertTrue($events->contains('title', 'Team planning'));
        $this->assertFalse($events->contains('title', 'Private meeting'));
        $this->assertNotEmpty($response->json('data.quote.text'));
        $this->assertSame(
            $response->json('data.quote'),
            $this->actingAs($user, 'sanctum')->getJson(route('dashboard.overview', [
                'from' => '2026-08-01',
                'to' => '2026-08-31',
            ]))->json('data.quote')
        );
    }

    private function workplaceUser(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $permission = Permission::firstOrCreate(
            ['slug' => 'view-workplace-hub'],
            ['model' => 'Workplace Hub', 'name' => 'Use Workplace Hub', 'description' => 'Use Workplace Hub']
        );
        $role->permissions()->syncWithoutDetaching($permission->id);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
