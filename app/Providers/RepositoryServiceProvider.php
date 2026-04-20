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
    }

    public function boot(): void {}
}
