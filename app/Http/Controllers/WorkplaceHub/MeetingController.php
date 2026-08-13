<?php

namespace App\Http\Controllers\WorkplaceHub;

use App\Http\Controllers\Controller;
use App\Http\Controllers\WorkplaceHub\Concerns\AuthorizesWorkplaceMeetings;
use App\Models\MeetingRoom;
use App\Models\User;
use App\Models\WorkplaceMeeting;
use App\Services\AuditLog\AuditLogServiceInterface;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MeetingController extends Controller
{
    use AuthorizesWorkplaceMeetings;

    private const RELATIONS = [
        'room',
        'organizer:id,first_name,middle_name,last_name,email,profile_photo_path,updated_at',
        'attendees:id,first_name,middle_name,last_name,email,profile_photo_path,updated_at',
        'attachments.uploader:id,first_name,middle_name,last_name',
        'actionItems.assignee:id,first_name,middle_name,last_name,email',
        'actionItems.creator:id,first_name,middle_name,last_name',
    ];

    public function __construct(private readonly AuditLogServiceInterface $auditLogService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])],
            'room_id' => ['nullable', 'uuid', 'exists:meeting_rooms,id'],
            'search' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $query = WorkplaceMeeting::query()->with(self::RELATIONS)->orderBy('starts_at');
        if (! $user->hasPermission('manage-company-meetings')) {
            $query->where(function (Builder $query) use ($user): void {
                $query->where('organizer_id', $user->id)
                    ->orWhereHas('attendees', fn (Builder $attendees) => $attendees->where('users.id', $user->id));
            });
        }
        if ($request->filled('from')) {
            $query->where('ends_at', '>=', $request->date('from')->startOfDay());
        }
        if ($request->filled('to')) {
            $query->where('starts_at', '<=', $request->date('to')->endOfDay());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('room_id')) {
            $query->where('room_id', $request->string('room_id'));
        }
        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(fn (Builder $query) => $query
                ->where('title', 'like', $search)
                ->orWhere('agenda', 'like', $search));
        }

        return response()->json(['data' => $query->paginate((int) $request->input('limit', 30))]);
    }

    public function show(Request $request, WorkplaceMeeting $meeting): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeViewMeeting($user, $meeting);

        return response()->json(['data' => $meeting->load(self::RELATIONS)]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasPermission('create-meetings')) {
            throw new AuthorizationException('You do not have permission to schedule meetings.');
        }

        $data = $this->validated($request, true);
        $occurrences = $this->occurrences($data);

        $seriesId = $occurrences->count() > 1 ? (string) Str::uuid() : null;
        $meetings = DB::transaction(function () use ($data, $occurrences, $seriesId, $user): Collection {
            $this->lockActiveRoom($data['room_id'] ?? null);
            foreach ($occurrences as [$startsAt, $endsAt]) {
                $this->ensureRoomAvailable($data['room_id'] ?? null, $startsAt, $endsAt);
            }

            return $occurrences->map(function (array $occurrence) use ($data, $seriesId, $user): WorkplaceMeeting {
                [$startsAt, $endsAt] = $occurrence;
                $meeting = WorkplaceMeeting::create([
                    'series_id' => $seriesId,
                    'room_id' => $data['room_id'] ?? null,
                    'organizer_id' => $user->id,
                    'title' => $data['title'],
                    'type' => $data['type'],
                    'agenda' => $data['agenda'] ?? null,
                    'decisions' => [],
                    'links' => $data['links'] ?? [],
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => 'scheduled',
                ]);
                $meeting->attendees()->sync($this->attendeeIds($data, $user));

                return $meeting;
            });
        });

        $first = $meetings->first();
        $this->auditLogService->insertLog($first, 'create', [
            'record_id' => $first->id,
            'series_id' => $seriesId,
            'occurrences' => $meetings->count(),
        ]);

        return response()->json([
            'message' => $meetings->count() > 1 ? "{$meetings->count()} recurring meetings scheduled successfully." : 'Meeting scheduled successfully.',
            'data' => $first->load(self::RELATIONS),
            'meta' => ['occurrences_created' => $meetings->count(), 'series_id' => $seriesId],
        ], 201);
    }

    public function update(Request $request, WorkplaceMeeting $meeting): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeManageMeeting($user, $meeting);
        if (in_array($meeting->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => 'Completed or cancelled meetings cannot be rescheduled.']);
        }

        $data = $this->validated($request, false);
        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = Carbon::parse($data['ends_at']);
        $before = $meeting->toArray();

        DB::transaction(function () use ($meeting, $data, $user, $startsAt, $endsAt): void {
            $this->lockActiveRoom($data['room_id'] ?? null);
            $this->ensureRoomAvailable($data['room_id'] ?? null, $startsAt, $endsAt, $meeting);
            $meeting->update([
                'room_id' => $data['room_id'] ?? null,
                'title' => $data['title'],
                'type' => $data['type'],
                'agenda' => $data['agenda'] ?? null,
                'minutes' => $data['minutes'] ?? $meeting->minutes,
                'decisions' => $data['decisions'] ?? $meeting->decisions,
                'links' => $data['links'] ?? $meeting->links,
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'status' => $data['status'] ?? $meeting->status,
                'cancelled_at' => ($data['status'] ?? null) === 'cancelled' ? now() : null,
            ]);
            $meeting->attendees()->sync($this->attendeeIds($data, $user));
        });

        $this->auditLogService->insertLog($meeting, 'update', [
            'record_id' => $meeting->id,
            'before' => $before,
            'after' => $meeting->fresh()->toArray(),
        ]);

        return response()->json(['message' => 'Meeting updated successfully.', 'data' => $meeting->fresh()->load(self::RELATIONS)], 202);
    }

    public function complete(Request $request, WorkplaceMeeting $meeting): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeManageMeeting($user, $meeting);
        if (in_array($meeting->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => 'Only an active meeting can be completed.']);
        }
        $data = $request->validate([
            'minutes' => ['nullable', 'string', 'max:100000'],
            'decisions' => ['nullable', 'array', 'max:100'],
            'decisions.*' => ['string', 'max:1000'],
        ]);
        $meeting->update(array_merge($data, ['status' => 'completed', 'completed_at' => now()]));
        $this->auditLogService->insertLog($meeting, 'complete', ['record_id' => $meeting->id]);

        return response()->json(['message' => 'Meeting completed successfully.', 'data' => $meeting->fresh()->load(self::RELATIONS)], 202);
    }

    public function destroy(Request $request, WorkplaceMeeting $meeting): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeManageMeeting($user, $meeting);
        if ($meeting->status === 'completed') {
            throw ValidationException::withMessages(['meeting' => 'Completed meetings are retained as company records.']);
        }

        $meeting->load('attachments');
        $before = $meeting->toArray();
        foreach ($meeting->attachments as $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }
        $meeting->delete();
        $this->auditLogService->insertLog($meeting, 'delete', ['record_id' => $meeting->id, 'before' => $before]);

        return response()->json(['message' => 'Meeting removed successfully.', 'data' => true]);
    }

    public function people(Request $request): JsonResponse
    {
        $people = User::query()
            ->select(['id', 'first_name', 'middle_name', 'last_name', 'email'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return response()->json(['data' => $people]);
    }

    private function validated(Request $request, bool $creating): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', Rule::in(['daily_standup', 'team_meeting', 'planning', 'review', 'one_on_one', 'training', 'other'])],
            'room_id' => ['nullable', 'uuid', Rule::exists('meeting_rooms', 'id')->where('status', 'active')],
            'agenda' => ['nullable', 'string', 'max:10000'],
            'minutes' => ['nullable', 'string', 'max:100000'],
            'decisions' => ['nullable', 'array', 'max:100'],
            'decisions.*' => ['string', 'max:1000'],
            'links' => ['nullable', 'array', 'max:20'],
            'links.*' => ['array:label,url'],
            'links.*.label' => ['required', 'string', 'max:100'],
            'links.*.url' => ['required', 'string', 'max:2048', 'url:http,https', 'distinct'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'attendee_ids' => ['nullable', 'array', 'max:500'],
            'attendee_ids.*' => ['uuid', 'distinct', 'exists:users,id'],
        ];
        if ($creating) {
            $rules['recurrence'] = ['nullable', Rule::in(['none', 'daily', 'weekdays', 'weekly'])];
            $rules['recurrence_until'] = ['nullable', 'date', 'after_or_equal:starts_at', 'before_or_equal:'.Carbon::parse($request->input('starts_at', now()))->addYear()->toDateString()];
        } else {
            $rules['status'] = ['nullable', Rule::in(['scheduled', 'in_progress', 'cancelled'])];
        }

        return $request->validate($rules);
    }

    private function occurrences(array $data): Collection
    {
        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = Carbon::parse($data['ends_at']);
        $recurrence = $data['recurrence'] ?? 'none';
        if ($recurrence === 'none') {
            return collect([[$startsAt, $endsAt]]);
        }

        if (empty($data['recurrence_until'])) {
            throw ValidationException::withMessages(['recurrence_until' => 'Choose an end date for a recurring meeting.']);
        }

        $until = Carbon::parse($data['recurrence_until'])->endOfDay();
        $durationSeconds = $startsAt->diffInSeconds($endsAt);
        $occurrences = collect();
        $cursor = $startsAt->copy();
        while ($cursor->lte($until) && $occurrences->count() < 90) {
            if ($recurrence !== 'weekdays' || ! $cursor->isWeekend()) {
                $occurrences->push([$cursor->copy(), $cursor->copy()->addSeconds($durationSeconds)]);
            }
            $cursor = $recurrence === 'weekly' ? $cursor->addWeek() : $cursor->addDay();
        }
        if ($cursor->lte($until)) {
            throw ValidationException::withMessages(['recurrence_until' => 'A recurring series is limited to 90 meetings.']);
        }

        return $occurrences;
    }

    private function ensureRoomAvailable(?string $roomId, Carbon $startsAt, Carbon $endsAt, WorkplaceMeeting $ignore = null): void
    {
        if (! $roomId) {
            return;
        }

        $conflict = WorkplaceMeeting::query()
            ->where('room_id', $roomId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($ignore, fn (Builder $query) => $query->whereKeyNot($ignore->id))
            ->first();
        if ($conflict) {
            $room = MeetingRoom::find($roomId);
            throw ValidationException::withMessages([
                'room_id' => "{$room?->name} is already booked for {$conflict->title} during this time.",
            ]);
        }
    }

    private function lockActiveRoom(?string $roomId): void
    {
        if (! $roomId) {
            return;
        }

        if (! MeetingRoom::query()->whereKey($roomId)->where('status', 'active')->lockForUpdate()->exists()) {
            throw ValidationException::withMessages(['room_id' => 'The selected meeting room is not active.']);
        }
    }

    private function attendeeIds(array $data, User $organizer): array
    {
        return collect($data['attendee_ids'] ?? [])->reject(fn (string $id) => $id === $organizer->id)->unique()->values()->all();
    }
}
