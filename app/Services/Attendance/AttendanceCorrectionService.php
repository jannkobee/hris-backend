<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\User;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Notifications\AppNotificationService;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceCorrectionService
{
    public function __construct(private readonly ResponseServiceInterface $response, private readonly AuditLogServiceInterface $audit, private readonly AppNotificationService $notifications)
    {
    }

    public function submit(array $data, string $employeeId): JsonResponse
    {
        $attendance = Attendance::query()->findOrFail($data['attendance_id']);
        if ($attendance->employee_id !== $employeeId) {
            throw new AuthorizationException('You can only correct your own attendance.');
        }
        if (AttendanceCorrectionRequest::query()->where('attendance_id', $attendance->id)->where('status', AttendanceCorrectionRequest::PENDING)->exists()) {
            throw ValidationException::withMessages(['attendance_id' => 'A correction request is already pending for this attendance record.']);
        }
        $request = AttendanceCorrectionRequest::create([...$data, 'employee_id' => $employeeId]);
        $this->audit->insertLog($request, 'create', ['record_id' => $request->id, 'after' => $request->toArray()]);
        User::query()->whereHas('role.permissions', fn ($query) => $query->where('slug', 'approve-attendance-corrections'))->get()->each(fn (User $user) => $this->notifications->send($user, 'attendance_correction_submitted', 'Attendance correction awaiting review', 'A correction request needs your review.', ['correction_id' => $request->id]));

        return $this->response->storeResponse('Attendance correction request', $request);
    }

    public function review(AttendanceCorrectionRequest $request, array $data, string $reviewerId): JsonResponse
    {
        if ($request->status !== AttendanceCorrectionRequest::PENDING) {
            throw ValidationException::withMessages(['status' => 'Only pending correction requests can be reviewed.']);
        }

        return DB::transaction(function () use ($request, $data, $reviewerId) {
            $before = $request->toArray();
            $request->update(['status' => $data['status'], 'reviewer_notes' => $data['reviewer_notes'] ?? null, 'reviewed_by' => $reviewerId, 'reviewed_at' => now()]);
            if ($data['status'] === AttendanceCorrectionRequest::APPROVED) {
                $attendance = Attendance::query()->findOrFail($request->attendance_id);
                $attendance->update(['time_in' => $request->requested_time_in, 'time_out' => $request->requested_time_out]);
                $this->audit->insertLog($attendance, 'attendance_correction_approved', ['correction_request_id' => $request->id]);
            }
            $this->audit->insertLog($request, 'review', ['record_id' => $request->id, 'before' => $before, 'after' => $request->toArray()]);
            $employeeUser = $request->employee?->user;
            if ($employeeUser) {
                $this->notifications->send($employeeUser, "attendance_correction_{$data['status']}", 'Attendance correction '.ucfirst($data['status']), $data['status'] === 'approved' ? 'Your attendance correction was approved.' : 'Your attendance correction was rejected.', ['correction_id' => $request->id]);
            }

            return $this->response->updateResponse('Attendance correction request', $request->fresh());
        });
    }
}
