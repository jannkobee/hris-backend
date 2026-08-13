<?php

namespace App\Http\Controllers\WorkplaceHub\Concerns;

use App\Models\User;
use App\Models\WorkplaceMeeting;
use Illuminate\Auth\Access\AuthorizationException;

trait AuthorizesWorkplaceMeetings
{
    private function canViewMeeting(User $user, WorkplaceMeeting $meeting): bool
    {
        return $user->hasPermission('manage-company-meetings')
            || $meeting->organizer_id === $user->id
            || $meeting->attendees()->where('users.id', $user->id)->exists();
    }

    private function canManageMeeting(User $user, WorkplaceMeeting $meeting): bool
    {
        return $user->hasPermission('manage-company-meetings')
            || $meeting->organizer_id === $user->id;
    }

    private function authorizeViewMeeting(User $user, WorkplaceMeeting $meeting): void
    {
        if (! $this->canViewMeeting($user, $meeting)) {
            throw new AuthorizationException('You are not a participant in this meeting.');
        }
    }

    private function authorizeManageMeeting(User $user, WorkplaceMeeting $meeting): void
    {
        if (! $this->canManageMeeting($user, $meeting)) {
            throw new AuthorizationException('Only the organizer or a meeting manager can change this meeting.');
        }
    }
}
