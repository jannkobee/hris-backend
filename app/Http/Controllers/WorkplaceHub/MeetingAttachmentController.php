<?php

namespace App\Http\Controllers\WorkplaceHub;

use App\Http\Controllers\Controller;
use App\Http\Controllers\WorkplaceHub\Concerns\AuthorizesWorkplaceMeetings;
use App\Models\MeetingAttachment;
use App\Models\User;
use App\Models\WorkplaceMeeting;
use App\Services\AuditLog\AuditLogServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MeetingAttachmentController extends Controller
{
    use AuthorizesWorkplaceMeetings;

    private AuditLogServiceInterface $auditLogService;

    public function __construct(AuditLogServiceInterface $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    public function store(Request $request, WorkplaceMeeting $meeting): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeViewMeeting($user, $meeting);
        $data = $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png,webp,zip'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $file = $data['file'];
        $disk = 'local';
        $path = $file->storeAs(
            "workplace/meetings/{$meeting->id}",
            Str::uuid().'.'.$file->getClientOriginalExtension(),
            $disk
        );
        $attachment = $meeting->attachments()->create([
            'uploaded_by' => $user->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'description' => $data['description'] ?? null,
        ]);
        $this->auditLogService->insertLog($attachment, 'upload', [
            'record_id' => $attachment->id,
            'meeting_id' => $meeting->id,
            'original_name' => $attachment->original_name,
        ]);

        return response()->json([
            'message' => 'Meeting file uploaded successfully.',
            'data' => $attachment->load('uploader:id,first_name,middle_name,last_name'),
        ], 201);
    }

    public function download(Request $request, MeetingAttachment $attachment): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $attachment->loadMissing('meeting');
        $this->authorizeViewMeeting($user, $attachment->meeting);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
        ]);
    }

    public function destroy(Request $request, MeetingAttachment $attachment): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $attachment->loadMissing('meeting');
        if ($attachment->uploaded_by !== $user->id && ! $this->canManageMeeting($user, $attachment->meeting)) {
            throw new AuthorizationException('Only the uploader or meeting organizer can remove this file.');
        }

        $before = $attachment->toArray();
        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();
        $this->auditLogService->insertLog($attachment, 'delete', ['record_id' => $attachment->id, 'before' => $before]);

        return response()->json(['message' => 'Meeting file removed successfully.', 'data' => true]);
    }
}
