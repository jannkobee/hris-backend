<?php

namespace App\Repository\Overtime;

use App\Repository\Base\BaseRepositoryInterface;
use Illuminate\Http\JsonResponse;

interface OvertimeRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Approve a pending overtime request.
     */
    public function approve(string $id, string $remarks = null): JsonResponse;

    /**
     * Reject a pending overtime request.
     */
    public function reject(string $id, string $remarks = null): JsonResponse;
}
