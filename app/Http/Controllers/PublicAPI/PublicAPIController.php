<?php

namespace App\Http\Controllers\PublicAPI;

use App\Http\Controllers\Controller;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PublicAPIController extends Controller
{
    protected ResponseServiceInterface $responseService;

    private string $baseUrl = 'https://countriesnow.space/api/v0.1/countries';

    public function __construct(ResponseServiceInterface $responseService)
    {
        $this->responseService = $responseService;
    }

    /**
     * Get all countries (and their ISO2 codes)
     * GET /countries
     */
    public function getCountries(): JsonResponse
    {
        try {
            $data = Cache::remember('locations.countries.v1', now()->addDay(), function () {
                $response = $this->client()->get("{$this->baseUrl}/iso")->throw();

                return collect($response->json('data'))->map(fn ($country): array => [
                    'name' => $country['name'],
                    'iso2' => $country['Iso2'] ?? null,
                ])->values()->all();
            });

            return $this->responseService->successResponse('Data', $data);
        } catch (\Exception $e) {
            return $this->responseService->rejectResponse($e->getMessage(), null);
        }
    }

    /**
     * Get all states of a specific country by Country Name
     * GET /countries/{countryName}/states
     */
    public function getStatesByCountry(string $countryName): JsonResponse
    {
        try {
            $country = urldecode($countryName);
            $cacheKey = 'locations.states.'.sha1(mb_strtolower($country)).'.v1';
            $data = Cache::remember($cacheKey, now()->addDay(), function () use ($country) {
                $response = $this->client()->post("{$this->baseUrl}/states", [
                    'country' => $country,
                ])->throw();

                return collect($response->json('data.states'))->map(fn ($state): array => [
                    'name' => $state['name'],
                    'iso2' => $state['state_code'] ?? null,
                ])->values()->all();
            });

            return $this->responseService->successResponse('Data', $data);
        } catch (\Exception $e) {
            return $this->responseService->rejectResponse($e->getMessage(), null);
        }
    }

    /**
     * Get all cities of a specific state by Country Name and State Name
     * GET /countries/{countryName}/states/{stateName}/cities
     */
    public function getCitiesByState(string $countryName, string $stateName): JsonResponse
    {
        try {
            $country = urldecode($countryName);
            $state = urldecode($stateName);
            $cacheKey = 'locations.cities.'.sha1(mb_strtolower("{$country}|{$state}")).'.v1';
            $data = Cache::remember($cacheKey, now()->addDay(), function () use ($country, $state) {
                $response = $this->client()->post("{$this->baseUrl}/state/cities", [
                    'country' => $country,
                    'state' => $state,
                ])->throw();

                return collect($response->json('data'))
                    ->map(fn ($cityName): array => ['name' => $cityName])
                    ->values()
                    ->all();
            });

            return $this->responseService->successResponse('Data', $data);
        } catch (\Exception $e) {
            return $this->responseService->rejectResponse($e->getMessage(), null);
        }
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->timeout(10)
            ->connectTimeout(5)
            ->retry(2, 200);
    }
}
