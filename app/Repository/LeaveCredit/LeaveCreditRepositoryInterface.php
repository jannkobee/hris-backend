<?php

namespace App\Repository\LeaveCredit;

use App\Models\LeaveCredit;
use App\Repository\Base\BaseRepositoryInterface;

interface LeaveCreditRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get the employee's credit bucket for a leave type/year, creating an
     * empty (0/0) one if it doesn't exist yet.
     */
    public function findOrCreateBucket(string $employeeId, string $leaveTypeId, int $year): LeaveCredit;

    /**
     * Add to total_earned for an existing bucket.
     */
    public function incrementEarned(LeaveCredit $credit, float $amount): LeaveCredit;
}
