<?php

namespace App\Repository\Attendance;

use App\Models\Attendance;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class AttendanceRepository extends BaseRepository implements AttendanceRepositoryInterface
{
    public function __construct(Attendance $model, ResponseServiceInterface $responseService, AuditLogServiceInterface $auditLogService)
    {
        parent::__construct($model, $responseService, $auditLogService);
    }

    public function create(array $attributes): JsonResponse
    {
        $date = $attributes['date'] ?? Carbon::today()->toDateString();

        $attributes['time_in'] = isset($attributes['time_in'])
            ? Carbon::parse($date . ' ' . $attributes['time_in'])->toDateTimeString()
            : null;

        $attributes['time_out'] = isset($attributes['time_out'])
            ? Carbon::parse($date . ' ' . $attributes['time_out'])->toDateTimeString()
            : null;

        return parent::create($attributes);
    }

    public function timeIn(string $employeeId, array $data): JsonResponse
    {
        $existing = $this->model->where('employee_id', $employeeId)
            ->whereDate('date', Carbon::today())
            ->first();

        if ($existing) {
            return $this->responseService->rejectResponse('You have already timed in for today.', null, 400);
        }

        $attendance = $this->model->create([
            'employee_id' => $employeeId,
            'date' => Carbon::today()->toDateString(),
            'time_in' => Carbon::now(),
            'time_in_notes' => $data['notes'] ?? null,
        ])->fresh();

        $this->auditLogService->insertLog($this->model, 'Time In', $attendance->toArray());

        return $this->responseService->storeResponse('Attendance', $attendance);
    }

    public function timeOut(string $employeeId, array $data): JsonResponse
    {
        $attendance = $this->model->where('employee_id', $employeeId)
            ->whereDate('date', Carbon::today())
            ->first();

        if (!$attendance) {
            return $this->responseService->rejectResponse('No time in record found for today.', null, 400);
        }

        if ($attendance->time_out) {
            return $this->responseService->rejectResponse('You have already timed out for today.', null, 400);
        }

        $attendance->update([
            'time_out' => Carbon::now(),
            'time_out_notes' => $data['notes'] ?? null,
        ]);

        $this->auditLogService->insertLog($this->model, 'Time Out', $attendance->toArray());

        return $this->responseService->successResponse('Successfully timed out.', $attendance->fresh());
    }

    public function getTodayAttendance(string $employeeId): JsonResponse
    {
        $attendance = $this->model->where('employee_id', $employeeId)
            ->whereDate('date', Carbon::today())
            ->first();

        return $this->responseService->successResponse('Today\'s attendance retrieved.', $attendance);
    }

    public function getUserHistory(string $employeeId, array $filters = []): JsonResponse
    {
        $limit = $filters['limit'] ?? 15;

        $history = $this->model->where('employee_id', $employeeId)
            ->orderBy('date', 'desc')
            ->paginate($limit);

        return $this->responseService->successResponse('Attendance history retrieved.', $history);
    }
}
