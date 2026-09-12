<?php

namespace Database\Seeders;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use Illuminate\Database\Seeder;

class ApprovalWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $workflows = [
            [
                'resource_type' => 'leave',
                'name' => 'Standard Leave Approval',
                'is_active' => true,
                'sla_hours' => 48,
            ],
            [
                'resource_type' => 'overtime',
                'name' => 'Standard Overtime Approval',
                'is_active' => true,
                'sla_hours' => 24,
            ],
            [
                'resource_type' => 'attendance_correction',
                'name' => 'Standard Attendance Correction Approval',
                'is_active' => true,
                'sla_hours' => 48,
            ],
        ];

        foreach ($workflows as $wf) {
            $workflow = ApprovalWorkflow::firstOrCreate(
                ['resource_type' => $wf['resource_type']],
                [
                    'name' => $wf['name'],
                    'is_active' => $wf['is_active'],
                ]
            );

            ApprovalWorkflowStep::firstOrCreate(
                [
                    'workflow_id' => $workflow->id,
                    'sequence' => 1,
                ],
                [
                    'approver_type' => 'manager',
                    'approver_id' => null,
                    'conditions' => null,
                    'sla_hours' => $wf['sla_hours'],
                ]
            );
        }
    }
}
