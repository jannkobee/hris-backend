<?php

namespace App\Providers;

use App\Repository\Base\BaseRepository;
use App\Repository\Base\BaseRepositoryInterface;
use App\Repository\Department\DepartmentRepository;
use App\Repository\Department\DepartmentRepositoryInterface;
use App\Repository\Employee\EmployeeRepository;
use App\Repository\Employee\EmployeeRepositoryInterface;
use App\Repository\Position\PositionRepository;
use App\Repository\Position\PositionRepositoryInterface;
use App\Repository\Role\RoleRepository;
use App\Repository\Role\RoleRepositoryInterface;
use App\Repository\RolePermission\RolePermissionRepository;
use App\Repository\RolePermission\RolePermissionRepositoryInterface;
use App\Repository\User\UserRepository;
use App\Repository\User\UserRepositoryInterface;
use App\Repository\EmploymentStatus\EmploymentStatusRepository;
use App\Repository\EmploymentStatus\EmploymentStatusRepositoryInterface;
use App\Repository\JobGrade\JobGradeRepository;
use App\Repository\JobGrade\JobGradeRepositoryInterface;
use App\Repository\Attendance\AttendanceRepository;
use App\Repository\Attendance\AttendanceRepositoryInterface;
use App\Repository\LeaveType\LeaveTypeRepository;
use App\Repository\LeaveType\LeaveTypeRepositoryInterface;
use App\Repository\LeaveRequest\LeaveRequestRepository;
use App\Repository\LeaveRequest\LeaveRequestRepositoryInterface;
use App\Repository\LeaveCredit\LeaveCreditRepository;
use App\Repository\LeaveCredit\LeaveCreditRepositoryInterface;
use App\Repository\Announcement\AnnouncementRepository;
use App\Repository\Announcement\AnnouncementRepositoryInterface;
use App\Repository\Conversation\ConversationRepository;
use App\Repository\Conversation\ConversationRepositoryInterface;
use App\Repository\LeaveCreditSetting\LeaveCreditSettingRepository;
use App\Repository\LeaveCreditSetting\LeaveCreditSettingRepositoryInterface;
use App\Repository\LeaveConversionRequest\LeaveConversionRequestRepository;
use App\Repository\LeaveConversionRequest\LeaveConversionRequestRepositoryInterface;
use App\Repository\Message\MessageRepository;
use App\Repository\Message\MessageRepositoryInterface;
use App\Repository\Note\NoteRepository;
use App\Repository\Note\NoteRepositoryInterface;
use App\Repository\ScheduledTask\ScheduledTaskRepository;
use App\Repository\ScheduledTask\ScheduledTaskRepositoryInterface;
use App\Repository\Overtime\OvertimeRepository;
use App\Repository\Overtime\OvertimeRepositoryInterface;
use App\Repository\Holiday\HolidayRepository;
use App\Repository\Holiday\HolidayRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BaseRepositoryInterface::class, BaseRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(RolePermissionRepositoryInterface::class, RolePermissionRepository::class);
        $this->app->bind(DepartmentRepositoryInterface::class, DepartmentRepository::class);
        $this->app->bind(PositionRepositoryInterface::class, PositionRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
        $this->app->bind(EmploymentStatusRepositoryInterface::class, EmploymentStatusRepository::class);
        $this->app->bind(JobGradeRepositoryInterface::class, JobGradeRepository::class);
        $this->app->bind(AttendanceRepositoryInterface::class, AttendanceRepository::class);
        $this->app->bind(LeaveTypeRepositoryInterface::class, LeaveTypeRepository::class);
        $this->app->bind(LeaveRequestRepositoryInterface::class, LeaveRequestRepository::class);
        $this->app->bind(LeaveCreditRepositoryInterface::class, LeaveCreditRepository::class);
        $this->app->bind(AnnouncementRepositoryInterface::class, AnnouncementRepository::class);
        $this->app->bind(LeaveCreditSettingRepositoryInterface::class, LeaveCreditSettingRepository::class);
        $this->app->bind(LeaveConversionRequestRepositoryInterface::class, LeaveConversionRequestRepository::class);
        $this->app->bind(ScheduledTaskRepositoryInterface::class, ScheduledTaskRepository::class);
        $this->app->bind(OvertimeRepositoryInterface::class, OvertimeRepository::class);
        $this->app->bind(ConversationRepositoryInterface::class, ConversationRepository::class);
        $this->app->bind(MessageRepositoryInterface::class, MessageRepository::class);
        $this->app->bind(NoteRepositoryInterface::class, NoteRepository::class);
        $this->app->bind(HolidayRepositoryInterface::class, HolidayRepository::class);
    }

    public function boot(): void {}
}
