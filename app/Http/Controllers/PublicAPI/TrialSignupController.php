<?php

namespace App\Http\Controllers\PublicAPI;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrialSignupRequest;
use App\Models\Organization;
use App\Services\Organizations\OrganizationProvisioningService;
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
        $organization = $this->provisioning->provision([
            'slug' => $data['slug'],
            'name' => $data['organization_name'],
            'country_code' => strtoupper($data['country_code']),
            'timezone' => $data['timezone'],
            'plan_code' => $data['plan_code'],
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
            'trial_ends_at' => now()->addDays((int) config('platform.trial_days')),
            'admin_first_name' => $data['first_name'],
            'admin_last_name' => $data['last_name'],
            'admin_email' => $data['email'],
            'admin_password' => $data['password'],
        ]);

        return $this->response->storeResponse('Trial organization', [
            'organization' => ['name' => $organization->name, 'slug' => $organization->slug, 'trial_ends_at' => $organization->trial_ends_at],
        ]);
    }
}
