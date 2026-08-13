<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
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
}
