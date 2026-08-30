<?php

namespace App\Http\Controllers\Overtime;

use App\Http\Controllers\Controller;
use App\Http\Requests\OvertimePolicyRequest;
use App\Models\OvertimePolicy;
use App\Services\Utils\ResponseServiceInterface;

class OvertimePolicyController extends Controller
{
    private ResponseServiceInterface $response;

    public function __construct(ResponseServiceInterface $response)
    {
        $this->response = $response;
        $this->middleware('permission:manage-overtimes');
    }

    public function index()
    {
        return $this->response->successResponse('Overtime policies', OvertimePolicy::query()->orderBy('day_type')->get());
    }

    public function store(OvertimePolicyRequest $r)
    {
        $m = OvertimePolicy::updateOrCreate(['day_type' => $r->validated('day_type')], $r->validated());

        return $this->response->storeResponse('Overtime policy', $m);
    }

    public function update(OvertimePolicyRequest $r, OvertimePolicy $overtimePolicy)
    {
        $overtimePolicy->update($r->validated());

        return $this->response->updateResponse('Overtime policy', $overtimePolicy->fresh());
    }

    public function destroy(OvertimePolicy $overtimePolicy)
    {
        $overtimePolicy->delete();

        return $this->response->deleteResponse('Overtime policy', true);
    }
}
