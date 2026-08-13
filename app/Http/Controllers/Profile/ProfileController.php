<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLog\AuditLogServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function __construct(private readonly AuditLogServiceInterface $auditLogService)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'role.permissions',
            'employee.department',
            'employee.position',
            'employee.employmentStatus',
            'employee.jobGrade',
            'employee.addresses',
            'employee.contacts',
        ]);

        return response()->json(['data' => $user]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:Male,Female,male,female,Other,other'],
            'birthday' => ['required', 'date', 'before:today'],
        ]);

        $before = $user->only(array_keys($data));
        $user->update($data);
        $this->auditLogService->insertLog($user, 'update profile', [
            'record_id' => $user->id,
            'before' => $before,
            'after' => $user->fresh()->only(array_keys($data)),
        ]);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => $user->fresh()->load('employee.department', 'employee.position'),
        ], 202);
    }

    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();
        $file = $request->file('photo');
        $disk = 'local';
        $path = $file->storeAs(
            "profile-photos/{$user->id}",
            Str::uuid().'.'.$file->extension(),
            $disk
        );

        if (! $path) {
            abort(500, 'The profile photo could not be stored.');
        }

        $oldDisk = $user->profile_photo_disk;
        $oldPath = $user->profile_photo_path;

        $user->update([
            'profile_photo_disk' => $disk,
            'profile_photo_path' => $path,
            'profile_photo_name' => $file->getClientOriginalName(),
            'profile_photo_mime' => $file->getMimeType(),
            'profile_photo_size' => $file->getSize(),
        ]);

        if ($oldDisk && $oldPath) {
            Storage::disk($oldDisk)->delete($oldPath);
        }

        $this->auditLogService->insertLog($user, 'update profile photo', ['record_id' => $user->id]);

        return response()->json([
            'message' => 'Profile photo updated successfully.',
            'data' => ['profile_photo_url' => $user->fresh()->profile_photo_url],
        ], 202);
    }

    public function deletePhoto(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->profile_photo_disk && $user->profile_photo_path) {
            Storage::disk($user->profile_photo_disk)->delete($user->profile_photo_path);
        }

        $user->update([
            'profile_photo_disk' => null,
            'profile_photo_path' => null,
            'profile_photo_name' => null,
            'profile_photo_mime' => null,
            'profile_photo_size' => null,
        ]);
        $this->auditLogService->insertLog($user, 'remove profile photo', ['record_id' => $user->id]);

        return response()->json(['message' => 'Profile photo removed successfully.', 'data' => null]);
    }

    public function photo(Request $request, User $user)
    {
        if (! $request->user()) {
            throw new AuthorizationException();
        }

        if (! $user->profile_photo_disk || ! $user->profile_photo_path) {
            abort(404, 'Profile photo not found.');
        }

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk($user->profile_photo_disk);
        if (! $storage->exists($user->profile_photo_path)) {
            abort(404, 'Profile photo not found.');
        }

        return $storage->response(
            $user->profile_photo_path,
            $user->profile_photo_name,
            ['Content-Type' => $user->profile_photo_mime ?: 'application/octet-stream']
        );
    }
}
