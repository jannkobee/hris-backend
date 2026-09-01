<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptOrganizationOwnerInvitationRequest;
use App\Http\Requests\StoreOrganizationOwnerInvitationRequest;
use App\Models\Organization;
use App\Services\Organizations\OrganizationOwnerInvitationService;
use App\Services\Utils\ResponseServiceInterface;

class OrganizationOwnerInvitationController extends Controller
{
    private OrganizationOwnerInvitationService $invitations;

    private ResponseServiceInterface $response;

    public function __construct(OrganizationOwnerInvitationService $invitations, ResponseServiceInterface $response)
    {
        $this->invitations = $invitations;
        $this->response = $response;
    }

    public function store(StoreOrganizationOwnerInvitationRequest $request, Organization $organization)
    {
        return $this->response->storeResponse(
            'Organization owner invitation',
            $this->invitations->invite($organization, $request->validated())
        );
    }

    public function accept(AcceptOrganizationOwnerInvitationRequest $request)
    {
        $owner = $this->invitations->accept($request->validated());

        return $this->response->storeResponse('Organization owner account', [
            'id' => $owner->id,
            'email' => $owner->email,
        ]);
    }
}
