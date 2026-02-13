<?php

namespace App\Repository\Employee;

use App\Repository\Base\BaseRepositoryInterface;
use Illuminate\Http\JsonResponse;

interface EmployeeRepositoryInterface extends BaseRepositoryInterface
{
    public function generateEmployeeNo(): JsonResponse;
}
