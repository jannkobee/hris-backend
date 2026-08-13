<?php

namespace App\Repository\LeaveRequest;

use App\Repository\Base\BaseRepositoryInterface;

interface LeaveRequestRepositoryInterface extends BaseRepositoryInterface
{
    public function approve(string $id);

    public function reject(string $id, string $remarks = null);

    public function cancel(string $id, string $remarks = null);

    public function downloadAttachment(string $id, string $attachment);
}
