<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadOrganizationBrandingLogoRequest;
use App\Services\Organizations\OrganizationBrandingService;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrganizationBrandingController extends Controller
{
    private OrganizationBrandingService $organizationBrandingService;

    private ResponseServiceInterface $responseService;

    public function __construct(
        OrganizationBrandingService $organizationBrandingService,
        ResponseServiceInterface $responseService
    ) {
        $this->organizationBrandingService = $organizationBrandingService;
        $this->responseService = $responseService;
        $this->middleware('permission:manage-organization-settings')->only(['uploadLogo', 'deleteLogo']);
    }

    public function show(): JsonResponse
    {
        return $this->responseService->successResponse(
            'Organization branding',
            $this->organizationBrandingService->branding()
        );
    }

    public function uploadLogo(UploadOrganizationBrandingLogoRequest $request): JsonResponse
    {
        /** @var UploadedFile $logo */
        $logo = $request->file('logo');

        return $this->responseService->updateResponse(
            'Organization branding',
            $this->organizationBrandingService->uploadLogo($logo)
        );
    }

    public function deleteLogo(): JsonResponse
    {
        return $this->responseService->deleteResponse(
            'Organization logo',
            $this->organizationBrandingService->removeLogo()
        );
    }

    public function logo(): StreamedResponse
    {
        return $this->organizationBrandingService->logoResponse();
    }
}
