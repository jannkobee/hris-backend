<?php

namespace App\Services\Utils;

use Illuminate\Http\JsonResponse;

interface ResponseServiceInterface
{
    public function resolveResponse(string $message, mixed $data, int $statusCode = 200): JsonResponse;

    public function rejectResponse(string $message, mixed $data, int $statusCode = 500): JsonResponse;

    public function successResponse(string $model, mixed $data): JsonResponse;

    public function storeResponse(string $model, mixed $data): JsonResponse;

    public function updateResponse(string $model, mixed $data): JsonResponse;

    public function deleteResponse(string $model, mixed $data): JsonResponse;

    public function restoreResponse(string $model, mixed $data): JsonResponse;
}
