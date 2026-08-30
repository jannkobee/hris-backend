<?php

namespace App\Http\Controllers\WorkplaceHub;

use App\Http\Controllers\Controller;
use App\Http\Controllers\WorkplaceHub\Concerns\AuthorizesWorkplaceMeetings;
use App\Models\MeetingActionItem;
use App\Models\User;
use App\Models\WorkplaceMeeting;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Rules\TenantRule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MeetingActionItemController extends Controller
{
    use AuthorizesWorkplaceMeetings;

    public function __construct(private readonly AuditLogServiceInterface $auditLogService)
    {
    }

    public function store(Request $request, WorkplaceMeeting $meeting): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeViewMeeting($user, $meeting);
        $data = $this->validated($request);
        $this->ensureAssigneeIsParticipant($meeting, $data['assigned_to'] ?? null);
        $actionItem = $meeting->actionItems()->create(array_merge($data, [
            'created_by' => $user->id,
            'completed_at' => ($data['status'] ?? 'open') === 'completed' ? now() : null,
        ]));
        $this->auditLogService->insertLog($actionItem, 'create', [
            'record_id' => $actionItem->id,
            'meeting_id' => $meeting->id,
        ]);

        return response()->json([
            'message' => 'Action item added successfully.',
            'data' => $actionItem->load('assignee:id,first_name,middle_name,last_name,email'),
        ], 201);
    }

    public function update(Request $request, MeetingActionItem $actionItem): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $actionItem->loadMissing('meeting');
        $this->authorizeViewMeeting($user, $actionItem->meeting);
        $data = $this->validated($request);
        $this->ensureAssigneeIsParticipant($actionItem->meeting, $data['assigned_to'] ?? null);
        $before = $actionItem->toArray();
        $data['completed_at'] = ($data['status'] ?? $actionItem->status) === 'completed' ? ($actionItem->completed_at ?? now()) : null;
        $actionItem->update($data);
        $this->auditLogService->insertLog($actionItem, 'update', [
            'record_id' => $actionItem->id,
            'before' => $before,
            'after' => $actionItem->fresh()->toArray(),
        ]);

        return response()->json([
            'message' => 'Action item updated successfully.',
            'data' => $actionItem->fresh()->load('assignee:id,first_name,middle_name,last_name,email'),
        ], 202);
    }

    public function destroy(Request $request, MeetingActionItem $actionItem): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $actionItem->loadMissing('meeting');
        if ($actionItem->created_by !== $user->id && ! $this->canManageMeeting($user, $actionItem->meeting)) {
            throw new AuthorizationException('Only the creator or meeting organizer can remove this action item.');
        }
        $before = $actionItem->toArray();
        $actionItem->delete();
        $this->auditLogService->insertLog($actionItem, 'delete', ['record_id' => $actionItem->id, 'before' => $before]);

        return response()->json(['message' => 'Action item removed successfully.', 'data' => true]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:220'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'uuid', TenantRule::exists('users')],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'status' => ['required', Rule::in(['open', 'in_progress', 'completed'])],
            'due_at' => ['nullable', 'date'],
        ]);
    }

    private function ensureAssigneeIsParticipant(WorkplaceMeeting $meeting, ?string $userId): void
    {
        if (! $userId || $meeting->organizer_id === $userId) {
            return;
        }

        if (! $meeting->attendees()->where('users.id', $userId)->exists()) {
            throw ValidationException::withMessages([
                'assigned_to' => 'Action items can only be assigned to the organizer or an attendee.',
            ]);
        }
    }
}
