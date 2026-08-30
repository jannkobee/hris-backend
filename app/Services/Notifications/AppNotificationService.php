<?php

namespace App\Services\Notifications;

use App\Models\AppNotification;
use App\Models\User;

class AppNotificationService
{
    public function send(User $user, string $type, string $title, string $body, array $data = []): void
    {
        AppNotification::create(['user_id' => $user->id, 'type' => $type, 'title' => $title, 'body' => $body, 'data' => $data]);
    }
}
