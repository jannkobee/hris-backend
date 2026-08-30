<?php

namespace App\Http\Controllers\WorkplaceHub;

use App\Http\Controllers\Controller;
use App\Models\MeetingRoom;
use App\Models\User;
use App\Models\WorkplaceMeeting;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Rules\TenantRule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MeetingRoomController extends Controller
{
    public function __construct(private readonly AuditLogServiceInterface $auditLogService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'starts_at' => ['nullable', 'date', 'required_with:ends_at'],
            'ends_at' => ['nullable', 'date', 'after:starts_at', 'required_with:starts_at'],
            'include_inactive' => ['nullable', 'boolean'],
            'ignore_meeting_id' => ['nullable', 'uuid', TenantRule::exists('workplace_meetings')],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $query = MeetingRoom::query()
            ->withCount(['meetings as upcoming_meetings_count' => fn ($query) => $query
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->where('ends_at', '>=', now())])
            ->withMin(['meetings as next_meeting_starts_at' => fn ($query) => $query
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->where('starts_at', '>=', now())], 'starts_at')
            ->orderBy('name');

        if (! $request->boolean('include_inactive')) {
            $query->where('status', 'active');
        }
        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(fn ($query) => $query
                ->where('name', 'like', $search)
                ->orWhere('code', 'like', $search)
                ->orWhere('location', 'like', $search)
                ->orWhere('floor', 'like', $search));
        }

        $rooms = $query->get();
        $startsAt = $request->filled(['starts_at', 'ends_at']) ? $request->date('starts_at') : now();
        $endsAt = $request->filled(['starts_at', 'ends_at']) ? $request->date('ends_at') : now()->addSecond();
        $busyRooms = WorkplaceMeeting::query()
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->whereNotNull('room_id')
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($request->filled('ignore_meeting_id'), fn ($query) => $query->whereKeyNot($request->string('ignore_meeting_id')))
            ->orderByDesc('ends_at')
            ->get(['room_id', 'ends_at'])
            ->keyBy('room_id');

        $rooms->each(function (MeetingRoom $room) use ($busyRooms): void {
            $busyMeeting = $busyRooms->get($room->id);
            $room->setAttribute('is_available', $room->status === 'active' && ! $busyMeeting);
            $room->setAttribute('busy_until', $busyMeeting?->ends_at);
        });

        return response()->json(['data' => $rooms]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeRoomManagement($request);
        $room = MeetingRoom::create(array_merge($this->validated($request), [
            'created_by' => $request->user()->id,
        ]));
        $this->auditLogService->insertLog($room, 'create', ['record_id' => $room->id, 'after' => $room->toArray()]);

        return response()->json(['message' => 'Meeting room created successfully.', 'data' => $room], 201);
    }

    public function update(Request $request, MeetingRoom $room): JsonResponse
    {
        $this->authorizeRoomManagement($request);
        $before = $room->toArray();
        $room->update($this->validated($request, $room));
        $this->auditLogService->insertLog($room, 'update', [
            'record_id' => $room->id,
            'before' => $before,
            'after' => $room->fresh()->toArray(),
        ]);

        return response()->json(['message' => 'Meeting room updated successfully.', 'data' => $room->fresh()], 202);
    }

    public function destroy(Request $request, MeetingRoom $room): JsonResponse
    {
        $this->authorizeRoomManagement($request);
        if ($room->meetings()->exists()) {
            throw ValidationException::withMessages([
                'room' => 'This room has meeting history. Mark it inactive instead of deleting it.',
            ]);
        }

        $before = $room->toArray();
        $room->delete();
        $this->auditLogService->insertLog($room, 'delete', ['record_id' => $room->id, 'before' => $before]);

        return response()->json(['message' => 'Meeting room removed successfully.', 'data' => true]);
    }

    private function validated(Request $request, MeetingRoom $room = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40', TenantRule::unique('meeting_rooms', 'code')->ignore($room?->id)],
            'location' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:80'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'amenities' => ['nullable', 'array', 'max:30'],
            'amenities.*' => ['string', 'max:80'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }

    private function authorizeRoomManagement(Request $request): void
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user?->hasPermission('manage-meeting-rooms')) {
            throw new AuthorizationException('You do not have permission to manage meeting rooms.');
        }
    }
}
