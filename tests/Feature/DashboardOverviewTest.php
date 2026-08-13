<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkplaceMeeting;
use Carbon\Carbon;
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

    public function test_every_authenticated_employee_can_see_safe_company_presence(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-13 10:00:00', 'Asia/Manila'));

        try {
            $role = Role::create(['name' => 'No management permissions']);
            $viewer = User::factory()->create(['role_id' => $role->id]);
            Employee::create([
                'user_id' => $viewer->id,
                'employee_no' => 'EMP-OUT',
            ]);

            $colleague = User::factory()->create(['role_id' => $role->id]);
            $colleagueEmployee = Employee::create([
                'user_id' => $colleague->id,
                'employee_no' => 'EMP-IN',
            ]);
            Attendance::create([
                'employee_id' => $colleagueEmployee->id,
                'date' => '2026-08-13',
                'time_in' => '2026-08-13 08:00:00',
                'time_in_latitude' => 14.5995,
                'time_in_longitude' => 120.9842,
                'time_in_notes' => 'Sensitive note',
            ]);

            $clockedOutUser = User::factory()->create(['role_id' => $role->id]);
            $clockedOutEmployee = Employee::create([
                'user_id' => $clockedOutUser->id,
                'employee_no' => 'EMP-CLOCKED-OUT',
            ]);
            Attendance::create([
                'employee_id' => $clockedOutEmployee->id,
                'date' => '2026-08-13',
                'time_in' => '2026-08-13 07:30:00',
                'time_out' => '2026-08-13 09:30:00',
            ]);

            $response = $this->actingAs($viewer, 'sanctum')->getJson(route('dashboard.overview', [
                'from' => '2026-08-01',
                'to' => '2026-08-31',
            ]))->assertOk()
                ->assertJsonCount(3, 'data.presence');

            $presence = collect($response->json('data.presence'));
            $this->assertSame('in', $presence->firstWhere('employee_no', 'EMP-IN')['status']);
            $this->assertSame('not_clocked_in', $presence->firstWhere('employee_no', 'EMP-OUT')['status']);
            $this->assertSame('clocked_out', $presence->firstWhere('employee_no', 'EMP-CLOCKED-OUT')['status']);
            $this->assertSame($colleague->full_name, $presence->firstWhere('employee_no', 'EMP-IN')['user']['full_name']);
            $this->assertArrayNotHasKey('time_in_latitude', $presence->firstWhere('employee_no', 'EMP-IN'));
            $this->assertArrayNotHasKey('time_in_notes', $presence->firstWhere('employee_no', 'EMP-IN'));
        } finally {
            Carbon::setTestNow();
        }
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
