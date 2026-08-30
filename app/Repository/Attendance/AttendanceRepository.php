<?php

namespace App\Repository\Attendance;

use App\Models\Attendance;
use App\Repository\Base\BaseRepository;
use App\Services\AppSettings\AppSettingService;
use App\Services\Attendance\AttendanceExceptionService;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class AttendanceRepository extends BaseRepository implements AttendanceRepositoryInterface
{
    private AppSettingService $settings;

    private AttendanceExceptionService $exceptions;

    public function __construct(
        Attendance $model,
        ResponseServiceInterface $responseService,
        AuditLogServiceInterface $auditLogService,
        AppSettingService $settings,
        AttendanceExceptionService $exceptions
    ) {
        parent::__construct($model, $responseService, $auditLogService);
        $this->settings = $settings;
        $this->exceptions = $exceptions;
    }

    public function create(array $attributes): JsonResponse
    {
        $response = parent::create($this->normalizeManualTimes($attributes));
        $attendance = $this->model->find($response->getData()->data->id);
        if ($attendance) {
            $this->exceptions->apply($attendance);
        }

        return $response;
    }

    public function update(array $attributes, string|int $id): JsonResponse
    {
        $response = parent::update($this->normalizeManualTimes($attributes), $id);
        $attendance = $this->model->find($id);
        if ($attendance) {
            $this->exceptions->apply($attendance);
        }

        return $response;
    }

    public function timeIn(string $employeeId, array $data): JsonResponse
    {
        $now = $this->companyNow();

        $existing = $this->model->where('employee_id', $employeeId)
            ->whereDate('date', $now->toDateString())
            ->first();

        if ($existing) {
            return $this->responseService->rejectResponse('You have already timed in for today.', null, 400);
        }

        $photo = $this->storeCapturePhoto($data['photo'] ?? null, $employeeId, 'time-in', $now);

        try {
            $attendance = $this->model->create(array_merge([
                'employee_id' => $employeeId,
                'date' => $now->toDateString(),
                'time_in' => $now->copy()->utc(),
                'time_in_notes' => $this->settings->get('attendance.notes_enabled', true) ? ($data['notes'] ?? null) : null,
                'time_in_latitude' => $data['latitude'] ?? null,
                'time_in_longitude' => $data['longitude'] ?? null,
                'time_in_accuracy' => $data['accuracy'] ?? null,
                'ip_address' => $this->settings->get('attendance.capture_ip_enabled', true) ? ($data['source_ip'] ?? null) : null,
            ], $photo))->fresh();
            $this->exceptions->apply($attendance);
        } catch (Throwable $exception) {
            $this->deleteStoredPhoto($photo['time_in_photo_disk'] ?? null, $photo['time_in_photo_path'] ?? null);
            throw $exception;
        }

        $this->auditLogService->insertLog($this->model, 'Time In', $attendance->toArray());

        return $this->responseService->storeResponse('Attendance', $attendance);
    }

    public function timeOut(string $employeeId, array $data): JsonResponse
    {
        $now = $this->companyNow();

        $attendance = $this->model->where('employee_id', $employeeId)
            ->whereDate('date', $now->toDateString())
            ->first();

        if (! $attendance) {
            return $this->responseService->rejectResponse('No time in record found for today.', null, 400);
        }

        if ($attendance->time_out) {
            return $this->responseService->rejectResponse('You have already timed out for today.', null, 400);
        }

        $photo = $this->storeCapturePhoto($data['photo'] ?? null, $employeeId, 'time-out', $now);

        try {
            $attendance->update(array_merge([
                'time_out' => $now->copy()->utc(),
                'time_out_notes' => $this->settings->get('attendance.notes_enabled', true) ? ($data['notes'] ?? null) : null,
                'time_out_latitude' => $data['latitude'] ?? null,
                'time_out_longitude' => $data['longitude'] ?? null,
                'time_out_accuracy' => $data['accuracy'] ?? null,
                'time_out_ip_address' => $this->settings->get('attendance.capture_ip_enabled', true) ? ($data['source_ip'] ?? null) : null,
            ], $photo));
        } catch (Throwable $exception) {
            $this->deleteStoredPhoto($photo['time_out_photo_disk'] ?? null, $photo['time_out_photo_path'] ?? null);
            throw $exception;
        }

        $this->auditLogService->insertLog($this->model, 'Time Out', $attendance->toArray());
        $this->exceptions->apply($attendance);

        return $this->responseService->updateResponse('Attendance', $attendance->fresh());
    }

    public function getTodayAttendance(string $employeeId): JsonResponse
    {
        $today = $this->companyNow()->toDateString();
        $attendance = $this->model->where('employee_id', $employeeId)
            ->whereDate('date', $today)
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

    public function getListByDate(string $date, array $filters = []): JsonResponse
    {
        $limit = $filters['limit'] ?? 10;

        $query = $this->model->whereDate('date', $date);

        if (! empty($filters['relations'])) {
            $query->with(explode(',', $filters['relations']));
        }

        $records = $query->orderBy('date', 'desc')->paginate($limit);

        return $this->responseService->successResponse('Attendance records retrieved.', $records);
    }

    private function companyNow(): Carbon
    {
        return Carbon::now($this->settings->get('organization.timezone', config('app.timezone')));
    }

    private function normalizeManualTimes(array $attributes): array
    {
        $timezone = (string) $this->settings->get('organization.timezone', config('app.timezone'));
        $date = $attributes['date'] ?? Carbon::now($timezone)->toDateString();

        foreach (['time_in', 'time_out'] as $field) {
            $attributes[$field] = filled($attributes[$field] ?? null)
                ? Carbon::createFromFormat('Y-m-d H:i', $date.' '.$attributes[$field], $timezone)
                    ->utc()
                    ->toDateTimeString()
                : null;
        }

        return $attributes;
    }

    private function storeCapturePhoto(?UploadedFile $file, string $employeeId, string $action, Carbon $now): array
    {
        if (! $file || ! $this->settings->get('attendance.photo_capture_enabled', true)) {
            return [];
        }

        $prefix = str_replace('-', '_', $action);
        $disk = 'local';
        $directory = "attendance-captures/{$employeeId}/{$now->toDateString()}";
        $filename = $action.'-'.Str::uuid().'.'.$file->extension();
        $path = $file->storeAs($directory, $filename, $disk);

        if (! $path) {
            throw new \RuntimeException('The attendance photo could not be stored.');
        }

        return [
            "{$prefix}_photo_disk" => $disk,
            "{$prefix}_photo_path" => $path,
            "{$prefix}_photo_name" => $file->getClientOriginalName(),
            "{$prefix}_photo_mime" => $file->getMimeType(),
            "{$prefix}_photo_size" => $file->getSize(),
        ];
    }

    private function deleteStoredPhoto(?string $disk, ?string $path): void
    {
        if ($disk && $path) {
            Storage::disk($disk)->delete($path);
        }
    }
}
