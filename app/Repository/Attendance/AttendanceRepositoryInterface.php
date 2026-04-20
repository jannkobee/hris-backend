<?php

namespace App\Repository\Attendance;

use App\Repository\Base\BaseRepositoryInterface;

interface AttendanceRepositoryInterface extends BaseRepositoryInterface
{
    public function timeIn(string $employeeId, array $data);

    public function timeOut(string $employeeId, array $data);

    public function getTodayAttendance(string $employeeId);

    public function getUserHistory(string $employeeId, array $filters = []);
}
