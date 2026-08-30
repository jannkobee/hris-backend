<?php

namespace App\Http\Controllers\LeaveBlackout;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveBlackoutDateRequest;
use App\Models\LeaveBlackoutDate;
use App\Services\Utils\ResponseServiceInterface;

class LeaveBlackoutDateController extends Controller
{
    private ResponseServiceInterface $response;

    public function __construct(ResponseServiceInterface $response)
    {
        $this->response = $response;
        $this->requireResourcePermissions('leave-credit-settings');
    }

    public function index()
    {
        return $this->response->successResponse('Leave blackout dates', LeaveBlackoutDate::query()->orderBy('start_date')->paginate(request()->integer('limit', 20)));
    }

    public function store(LeaveBlackoutDateRequest $r)
    {
        $m = LeaveBlackoutDate::create($r->validated());

        return $this->response->storeResponse('Leave blackout date', $m);
    }

    public function update(LeaveBlackoutDateRequest $r, LeaveBlackoutDate $leaveBlackoutDate)
    {
        $leaveBlackoutDate->update($r->validated());

        return $this->response->updateResponse('Leave blackout date', $leaveBlackoutDate->fresh());
    }

    public function destroy(LeaveBlackoutDate $leaveBlackoutDate)
    {
        $leaveBlackoutDate->delete();

        return $this->response->deleteResponse('Leave blackout date', true);
    }
}
