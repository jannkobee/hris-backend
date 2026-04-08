<?php

namespace App\Repository\Attendance;

use App\Repository\Base\BaseRepositoryInterface;

interface AttendanceRepositoryInterface extends BaseRepositoryInterface
{
    public function timeIn(int $userId, array $data);

    public function timeOut(int $userId, array $data);

    public function getTodayAttendance(int $userId);

    public function getUserHistory(int $userId, array $filters = []);
}
