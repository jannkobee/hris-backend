<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProvisionOrganizationRequest;
use App\Http\Requests\UpdateOrganizationSubscriptionRequest;
use App\Models\Organization;
use App\Services\Organizations\OrganizationProvisioningService;
use App\Services\Plans\PlanEntitlementService;
use App\Services\Utils\ResponseServiceInterface;

class OrganizationProvisioningController extends Controller
{
    private OrganizationProvisioningService $provisioning;

    private PlanEntitlementService $entitlements;

    private ResponseServiceInterface $responseService;

    public function __construct(
        OrganizationProvisioningService $provisioning,
        PlanEntitlementService $entitlements,
        ResponseServiceInterface $responseService,
    ) {
        $this->provisioning = $provisioning;
        $this->entitlements = $entitlements;
        $this->responseService = $responseService;
    }

    public function store(ProvisionOrganizationRequest $request)
    {
        $organization = $this->provisioning->provision($request->validated());

        return $this->responseService->storeResponse(
            'Organization',
            $this->payload($organization)
        );
    }

    public function updateSubscription(UpdateOrganizationSubscriptionRequest $request, Organization $organization)
    {
        $organization = $this->provisioning->updateSubscription($organization, $request->validated());

        return $this->responseService->updateResponse(
            'Organization subscription',
            $this->payload($organization)
        );
    }

    private function payload(Organization $organization): array
    {
        return [
            ...$organization->toArray(),
            'plan' => $this->entitlements->payload($organization),
        ];
    }
}
