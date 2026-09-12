<?php

namespace App\Http\Controllers\PublicAPI;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrialSignupRequest;
use App\Models\Organization;
use App\Services\Organizations\OrganizationProvisioningService;
use App\Services\Organizations\OrganizationWorkspaceUrl;
use App\Services\Utils\ResponseServiceInterface;

class TrialSignupController extends Controller
{
    private OrganizationProvisioningService $provisioning;

    private ResponseServiceInterface $response;

    public function __construct(OrganizationProvisioningService $provisioning, ResponseServiceInterface $response)
    {
        $this->provisioning = $provisioning;
        $this->response = $response;
    }

    public function store(TrialSignupRequest $request)
    {
        $data = $request->validated();
        $planCode = $data['plan_code'] ?? 'basic_free';
        $free = $planCode === 'basic_free';
        $organization = $this->provisioning->provision([
            'slug' => $this->provisioning->generateAvailableSlug($data['organization_name']),
            'name' => $data['organization_name'],
            'country_code' => strtoupper($data['country_code']),
            'timezone' => $data['timezone'],
            'plan_code' => $planCode,
            'subscription_status' => $free ? Organization::SUBSCRIPTION_ACTIVE : Organization::SUBSCRIPTION_TRIALING,
            'trial_ends_at' => $free ? null : now()->addDays((int) config('platform.trial_days')),
            'admin_first_name' => $data['first_name'],
            'admin_last_name' => $data['last_name'],
            'admin_email' => $data['email'],
            'admin_password' => $data['password'],
        ]);

        return $this->response->storeResponse('Trial organization', [
            'login_url' => app(OrganizationWorkspaceUrl::class)->login($organization),
            'organization' => ['name' => $organization->name, 'slug' => $organization->slug, 'plan_code' => $organization->plan_code, 'subscription_status' => $organization->subscription_status, 'trial_ends_at' => $organization->trial_ends_at],
        ]);
    }
}
