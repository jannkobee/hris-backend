<?php

namespace App\Services\Approvals;

use App\Models\ApprovalDelegation;
use App\Models\User;

class DelegatedApproverResolver
{
    public function activeDelegate(User $approver): ?User
    {
        $delegation = ApprovalDelegation::query()->where('delegator_id', $approver->id)->whereDate('starts_on', '<=', today())->whereDate('ends_on', '>=', today())->latest('created_at')->first();

        return $delegation?->delegate;
    }

    public function canApprove(User $user, string $permission): bool
    {
        if ($user->hasPermission($permission)) {
            return true;
        }

        return ApprovalDelegation::query()->with('delegator.role.permissions')->where('delegate_id', $user->id)->whereDate('starts_on', '<=', today())->whereDate('ends_on', '>=', today())->get()->contains(fn (ApprovalDelegation $delegation) => $delegation->delegator?->hasPermission($permission));
    }
}
