<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovalDelegationRequest;
use App\Models\ApprovalDelegation;
use App\Services\Utils\ResponseServiceInterface;

class ApprovalDelegationController extends Controller
{
    private ResponseServiceInterface $response;

    public function __construct(ResponseServiceInterface $response)
    {
        $this->response = $response;
        $this->middleware('permission:manage-users');
    }

    public function index()
    {
        return $this->response->successResponse('Approval delegations', ApprovalDelegation::query()->with(['delegator', 'delegate'])->orderByDesc('starts_on')->paginate(request()->integer('limit', 20)));
    }

    public function store(ApprovalDelegationRequest $r)
    {
        $m = ApprovalDelegation::create($r->validated());

        return $this->response->storeResponse('Approval delegation', $m);
    }

    public function update(ApprovalDelegationRequest $r, ApprovalDelegation $approvalDelegation)
    {
        $approvalDelegation->update($r->validated());

        return $this->response->updateResponse('Approval delegation', $approvalDelegation->fresh());
    }

    public function destroy(ApprovalDelegation $approvalDelegation)
    {
        $approvalDelegation->delete();

        return $this->response->deleteResponse('Approval delegation', true);
    }
}
