<?php

namespace App\Http\Controllers\PublicAPI;

use App\Http\Controllers\Controller;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class PublicAPIController extends Controller
{
    protected $responseService;

    public function __construct(ResponseServiceInterface $responseService)
    {
        $this->responseService = $responseService;
    }

    /**
     * Get API headers with authentication for Country State City API
     */
    private function getCountryStateCityHeaders(): array
    {
        $apiKey = (string) config('services.countrystatecity.key', '');

        if ($apiKey === '') {
            throw new \Exception('COUNTRY_API_KEY not configured');
        }

        return [
            'X-CSCAPI-KEY' => $apiKey,
        ];
    }

    /**
     * Make HTTP request to Country State City API
     */
    private function makeCountryStateCityRequest(string $endpoint): JsonResponse
    {
        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders($this->getCountryStateCityHeaders())
                ->timeout(10)
                ->connectTimeout(5)
                ->get('https://api.countrystatecity.in/v1' . $endpoint)
                ->throw();

            return $this->responseService->successResponse(
                'Data',
                $response->json()
            );
        } catch (\Exception $e) {
            return $this->responseService->rejectResponse(
                $e->getMessage(),
                null
            );
        }
    }

    /**
     * Get all countries
     * GET /countries
     */
    public function getCountries(): JsonResponse
    {
        return $this->makeCountryStateCityRequest('/countries');
    }

    /**
     * Get specific country by ISO2 code
     * GET /countries/{iso2}
     */
    public function getCountry(string $iso2): JsonResponse
    {
        return $this->makeCountryStateCityRequest("/countries/{$iso2}");
    }

    /**
     * Get all states of a specific country
     * GET /countries/{countryIso2}/states
     */
    public function getStatesByCountry(string $countryIso2): JsonResponse
    {
        return $this->makeCountryStateCityRequest("/countries/{$countryIso2}/states");
    }

    /**
     * Get specific state by ISO2 code within a country
     * GET /countries/{countryIso2}/states/{stateIso2}
     */
    public function getState(string $countryIso2, string $stateIso2): JsonResponse
    {
        return $this->makeCountryStateCityRequest("/countries/{$countryIso2}/states/{$stateIso2}");
    }

    /**
     * Get all cities of a specific country
     * GET /countries/{countryIso2}/cities
     */
    public function getCitiesByCountry(string $countryIso2): JsonResponse
    {
        return $this->makeCountryStateCityRequest("/countries/{$countryIso2}/cities");
    }

    /**
     * Get all cities of a specific state within a country
     * GET /countries/{countryIso2}/states/{stateIso2}/cities
     */
    public function getCitiesByState(string $countryIso2, string $stateIso2): JsonResponse
    {
        return $this->makeCountryStateCityRequest("/countries/{$countryIso2}/states/{$stateIso2}/cities");
    }
}
