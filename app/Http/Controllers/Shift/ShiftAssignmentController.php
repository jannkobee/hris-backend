<?php

namespace App\Http\Controllers\Shift;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShiftAssignmentRequest;
use App\Models\ShiftAssignment;
use App\Services\Shifts\ShiftRosterService;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\Request;

class ShiftAssignmentController extends Controller
{
    private ShiftRosterService $roster;

    private ResponseServiceInterface $response;

    public function __construct(
        ShiftRosterService $roster,
        ResponseServiceInterface $response,
    ) {
        $this->roster = $roster;
        $this->response = $response;
        $this->requireResourcePermissions('shifts');
    }

    public function index(Request $request)
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'employee_id' => ['nullable', 'uuid'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        return $this->roster->listAssignments($request->only(['from', 'to', 'employee_id', 'limit']));
    }

    public function store(ShiftAssignmentRequest $request)
    {
        return $this->roster->createAssignment($request->validated());
    }

    public function show(ShiftAssignment $shiftAssignment)
    {
        return $this->response->successResponse('Shift assignment', $shiftAssignment->load(['employee.user', 'shiftTemplate']));
    }

    public function update(ShiftAssignmentRequest $request, ShiftAssignment $shiftAssignment)
    {
        return $this->roster->updateAssignment($shiftAssignment, $request->validated());
    }

    public function destroy(ShiftAssignment $shiftAssignment)
    {
        return $this->roster->deleteAssignment($shiftAssignment);
    }
}
