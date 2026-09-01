<?php

namespace App\Services\Approvals;

use App\Models\ApprovalWorkflow;
use App\Models\User;

class ApprovalWorkflowResolver
{
    public function nextApprover(string $resourceType, User $requester, array $context = []): ?User
    {
        $workflow = ApprovalWorkflow::query()->where('resource_type', $resourceType)->where('is_active', true)->with('steps')->first();
        if (! $workflow) {
            return null;
        }

        foreach ($workflow->steps as $step) {
            if (! $this->matches($step->conditions ?? [], $context)) {
                continue;
            }
            if ($step->approver_type === 'manager') {
                return $requester->employee?->manager?->user;
            }
            if ($step->approver_type === 'user' && $step->approver_id) {
                return User::query()->find($step->approver_id);
            }
            if ($step->approver_type === 'role' && $step->approver_id) {
                return User::query()->where('role_id', $step->approver_id)->where('is_active', true)->orderBy('created_at')->first();
            }
        }

        return null;
    }

    private function matches(array $conditions, array $context): bool
    {
        foreach ($conditions as $field => $expected) {
            if (($context[$field] ?? null) != $expected) {
                return false;
            }
        }

        return true;
    }
}
