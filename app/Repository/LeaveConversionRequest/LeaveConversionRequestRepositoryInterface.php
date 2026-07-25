<?php

namespace App\Repository\LeaveConversionRequest;

use App\Repository\Base\BaseRepositoryInterface;
use Illuminate\Http\JsonResponse;

interface LeaveConversionRequestRepositoryInterface extends BaseRepositoryInterface
{
    public function approve(string $id): JsonResponse;

    public function reject(string $id, ?string $remarks = null): JsonResponse;
}
