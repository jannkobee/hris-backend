<?php

namespace Tests\Unit;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use App\Models\Pivots\ConversationParticipant;
use App\Models\ScheduledTask;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ModelDateTimeCastTest extends TestCase
{
    #[DataProvider('calendarDateProvider')]
    public function test_calendar_dates_are_carbon_instances_and_serialize_as_year_month_day(
        Model $model,
        string $attribute
    ): void {
        $model->setRawAttributes([$attribute => '2026-08-13'], true);

        $this->assertInstanceOf(CarbonInterface::class, $model->getAttribute($attribute));
        $this->assertSame('2026-08-13', $model->attributesToArray()[$attribute]);
    }

    public static function calendarDateProvider(): array
    {
        return [
            'user birthday' => [new User(), 'birthday'],
            'employee hire date' => [new Employee(), 'hire_date'],
            'announcement publish date' => [new Announcement(), 'published_at'],
            'attendance date' => [new Attendance(), 'date'],
            'leave start date' => [new LeaveRequest(), 'start_date'],
            'leave end date' => [new LeaveRequest(), 'end_date'],
            'overtime date' => [new Overtime(), 'date'],
        ];
    }

    #[DataProvider('timeOfDayProvider')]
    public function test_time_only_values_serialize_for_frontend_time_inputs(
        Model $model,
        string $attribute
    ): void {
        $model->setRawAttributes([$attribute => '17:45:00'], true);

        $this->assertSame('17:45', $model->getAttribute($attribute));
        $this->assertSame('17:45', $model->attributesToArray()[$attribute]);

        $model->setAttribute($attribute, '08:30');

        $this->assertSame('08:30:00', $model->getAttributes()[$attribute]);
        $this->assertSame('08:30', $model->getAttribute($attribute));
    }

    public static function timeOfDayProvider(): array
    {
        return [
            'leave start time' => [new LeaveRequest(), 'start_time'],
            'leave end time' => [new LeaveRequest(), 'end_time'],
            'overtime start time' => [new Overtime(), 'time_start'],
            'overtime end time' => [new Overtime(), 'time_end'],
            'scheduled task run time' => [new ScheduledTask(), 'run_time'],
        ];
    }

    public function test_conversation_last_read_timestamp_is_iso_serializable(): void
    {
        $participant = new ConversationParticipant();
        $participant->setRawAttributes(['last_read_at' => '2026-08-13 17:45:00'], true);

        $this->assertInstanceOf(CarbonInterface::class, $participant->last_read_at);
        $this->assertSame(
            '2026-08-13T17:45:00.000000Z',
            $participant->attributesToArray()['last_read_at']
        );
    }
}
