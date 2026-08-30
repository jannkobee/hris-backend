<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\Request;

class AppNotificationController extends Controller
{
    private ResponseServiceInterface $response;

    public function __construct(ResponseServiceInterface $response)
    {
        $this->response = $response;
    }

    public function index(Request $request)
    {
        return $this->response->successResponse('Notifications', AppNotification::query()->where('user_id', $request->user()->id)->latest()->paginate($request->integer('limit', 20)));
    }

    public function read(Request $request, AppNotification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['read_at' => now()]);

        return $this->response->updateResponse('Notification', $notification);
    }
}
