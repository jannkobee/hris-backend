<?php

namespace App\Http\Controllers\PublicAPI;

use App\Http\Controllers\Controller;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class PublicAPIController extends Controller
{
    protected $responseService;
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
            $response = Http::acceptJson()
                ->timeout(10)
                ->connectTimeout(5)
                ->get("{$this->baseUrl}/iso")
                ->throw();

            // Map the response to match the old API's structure for the frontend
            $data = collect($response->json('data'))->map(function ($country) {
                return [
                    'name' => $country['name'],
                    'iso2' => $country['Iso2'] ?? null,
                ];
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
            // CountriesNow uses POST for states
            $response = Http::acceptJson()
                ->timeout(10)
                ->connectTimeout(5)
                ->post("{$this->baseUrl}/states", [
                    'country' => urldecode($countryName)
                ])
                ->throw();

            $data = collect($response->json('data.states'))->map(function ($state) {
                return [
                    'name' => $state['name'],
                    'iso2' => $state['state_code'] ?? null,
                ];
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
            // CountriesNow uses POST for cities
            $response = Http::acceptJson()
                ->timeout(10)
                ->connectTimeout(5)
                ->post("{$this->baseUrl}/state/cities", [
                    'country' => urldecode($countryName),
                    'state' => urldecode($stateName)
                ])
                ->throw();

            // CountriesNow returns an array of strings for cities. 
            // Map to an array of objects to keep frontend compatibility.
            $data = collect($response->json('data'))->map(function ($cityName) {
                return ['name' => $cityName];
            });

            return $this->responseService->successResponse('Data', $data);
        } catch (\Exception $e) {
            return $this->responseService->rejectResponse($e->getMessage(), null);
        }
    }
}
