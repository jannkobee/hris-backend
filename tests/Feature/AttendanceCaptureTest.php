<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AppSettings\AppSettingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_time_in_and_time_out_use_the_linked_employee(): void
    {
        $role = Role::create(['name' => 'User']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_no' => 'EMP-TEST-001',
        ]);

        $timeIn = $this->actingAs($user, 'sanctum')->postJson(
            route('attendances.time-in'),
            [
                'notes' => 'Started work',
                'latitude' => 14.5995,
                'longitude' => 120.9842,
                'accuracy' => 12.5,
            ]
        );

        $timeIn->assertCreated()
            ->assertJsonPath('data.employee_id', $employee->id)
            ->assertJsonPath('data.time_in_latitude', 14.5995);

        $timeOut = $this->actingAs($user, 'sanctum')->postJson(
            route('attendances.time-out'),
            [
                'latitude' => 14.5996,
                'longitude' => 120.9843,
                'accuracy' => 10,
            ]
        );

        $timeOut->assertAccepted()
            ->assertJsonPath('data.employee_id', $employee->id);

        $attendance = Attendance::where('employee_id', $employee->id)->firstOrFail();
        $this->assertNotNull($attendance->time_out);
        $this->assertSame(14.5996, $attendance->time_out_latitude);
        $this->assertDatabaseHas('audit_logs', [
            'module' => Attendance::class,
            'action' => 'Time In',
        ]);
    }

    public function test_manual_attendance_times_are_interpreted_in_the_company_timezone(): void
    {
        app(AppSettingService::class)->update(['organization.timezone' => 'Asia/Manila']);

        $role = Role::create(['name' => 'Attendance manager']);
        $permission = Permission::create([
            'slug' => 'manage-attendances',
            'model' => 'Attendance',
            'name' => 'Manage attendance',
            'description' => 'Manage attendance records',
        ]);
        $role->permissions()->attach($permission->id);
        $user = User::factory()->create(['role_id' => $role->id]);
        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_no' => 'EMP-TIMEZONE',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson(route('attendances.store'), [
            'employee_id' => $employee->id,
            'date' => '2026-08-14',
            'time_in' => '08:30',
            'time_out' => '17:30',
        ])->assertCreated();

        $attendance = Attendance::findOrFail($response->json('data.id'));
        $this->assertSame('2026-08-14 00:30:00', $attendance->getRawOriginal('time_in'));
        $this->assertSame('2026-08-14 09:30:00', $attendance->getRawOriginal('time_out'));
        $response->assertJsonPath('data.time_in', '2026-08-14T00:30:00.000000Z');

        $this->actingAs($user, 'sanctum')->putJson(route('attendances.update', $attendance), [
            'employee_id' => $employee->id,
            'date' => '2026-08-14',
            'time_in' => '09:15',
            'time_out' => null,
        ])->assertAccepted();

        $attendance->refresh();
        $this->assertSame('2026-08-14 01:15:00', $attendance->getRawOriginal('time_in'));
        $this->assertNull($attendance->getRawOriginal('time_out'));
    }

    public function test_live_capture_stores_the_company_instant_in_utc(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-14 08:30:00', 'Asia/Manila'));

        try {
            $role = Role::create(['name' => 'Timezone user']);
            $user = User::factory()->create(['role_id' => $role->id]);
            $employee = Employee::create([
                'user_id' => $user->id,
                'employee_no' => 'EMP-LIVE-TIMEZONE',
            ]);

            $this->actingAs($user, 'sanctum')->postJson(route('attendances.time-in'))
                ->assertCreated()
                ->assertJsonPath('data.time_in', '2026-08-14T00:30:00.000000Z');

            $attendance = Attendance::where('employee_id', $employee->id)->firstOrFail();
            $this->assertSame('2026-08-14', $attendance->date->toDateString());
            $this->assertSame('2026-08-14 00:30:00', $attendance->getRawOriginal('time_in'));
        } finally {
            Carbon::setTestNow();
        }
    }
}
