<?php

return [
    'default' => env('DEFAULT_ORGANIZATION_PLAN', 'growth'),

    'features' => [
        'core_hr' => [
            'name' => 'Core HR',
            'description' => 'Users, roles, employee records, organization setup, and the dashboard.',
        ],
        'attendance' => [
            'name' => 'Attendance',
            'description' => 'Employee time in/out and attendance administration.',
        ],
        'leave' => [
            'name' => 'Leave Management',
            'description' => 'Leave requests, balances, conversions, approvals, and policy setup.',
        ],
        'overtime' => [
            'name' => 'Overtime',
            'description' => 'Overtime requests, review, and approval.',
        ],
        'workforce_calendar' => [
            'name' => 'Workforce Calendar',
            'description' => 'Company holidays and special working days.',
        ],
        'announcements' => [
            'name' => 'Announcements',
            'description' => 'Company announcements and dashboard notices.',
        ],
        'messaging' => [
            'name' => 'Messaging',
            'description' => 'Direct and group workplace conversations.',
        ],
        'notes' => [
            'name' => 'Personal Notes',
            'description' => 'Private tenant-scoped notes for each authenticated user.',
        ],
        'employee_documents' => [
            'name' => 'Employee 201 Files',
            'description' => 'Private employee document storage and lifecycle management.',
        ],
        'payroll' => [
            'name' => 'Payroll',
            'description' => 'Payroll calculation, review, approval, payment, and employee payslips.',
        ],
        'workplace_hub' => [
            'name' => 'Workplace Hub',
            'description' => 'Meeting rooms, recurring meetings, minutes, decisions, and action items.',
        ],
        'audit_logs' => [
            'name' => 'Audit Logs',
            'description' => 'Searchable administrative and security audit history.',
        ],
        'automation' => [
            'name' => 'Automation',
            'description' => 'Managed scheduled tasks and automated HR operations.',
        ],
        'shift_rosters' => [
            'name' => 'Shift Rosters',
            'description' => 'Shift templates, roster assignments, and attendance exception detection.',
        ],
        'attendance_corrections' => [
            'name' => 'Attendance Corrections',
            'description' => 'Employee correction requests, manager review, and audit trail.',
        ],
        'reports' => [
            'name' => 'Operational Reports',
            'description' => 'Attendance, leave, overtime, payroll, and workforce-cost reports.',
        ],
        'scheduled_reports' => [
            'name' => 'Scheduled Reports',
            'description' => 'Saved reports and scheduled CSV delivery by email.',
        ],
        'integrations' => [
            'name' => 'Integrations',
            'description' => 'API tokens and signed webhook subscriptions.',
        ],
        'sso_scim' => [
            'name' => 'SSO and SCIM',
            'description' => 'Enterprise identity configuration and provisioning credentials.',
        ],
    ],

    'plans' => [
        'starter' => [
            'name' => 'Starter',
            'description' => 'Essential HR operations for small teams.',
            'features' => [
                'core_hr',
                'attendance',
                'leave',
                'overtime',
                'workforce_calendar',
                'announcements',
            ],
            'limits' => ['employees' => 25],
        ],
        'growth' => [
            'name' => 'Growth',
            'description' => 'Manager workflows and operational visibility for growing organizations.',
            'features' => [
                'core_hr', 'attendance', 'leave', 'overtime', 'workforce_calendar',
                'announcements', 'messaging', 'notes', 'employee_documents',
                'shift_rosters', 'attendance_corrections', 'reports',
            ],
            'limits' => ['employees' => 100],
        ],
        'business' => [
            'name' => 'Business',
            'description' => 'Payroll, automation, and controls for multi-team operations.',
            'features' => [
                'core_hr', 'attendance', 'leave', 'overtime', 'workforce_calendar',
                'announcements', 'messaging', 'notes', 'employee_documents',
                'shift_rosters', 'attendance_corrections', 'reports', 'payroll',
                'workplace_hub', 'audit_logs', 'automation', 'scheduled_reports',
            ],
            'limits' => ['employees' => 500],
        ],
        'basic' => [
            'name' => 'Basic (Legacy)',
            'description' => 'Legacy plan retained for existing organizations. Migrate to Growth when ready.',
            'features' => [
                'core_hr', 'attendance', 'leave', 'overtime', 'workforce_calendar',
                'announcements', 'messaging', 'notes',
            ],
            'limits' => ['employees' => 50],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'description' => 'Advanced identity, integrations, and governance for large organizations.',
            'features' => [
                'core_hr', 'attendance', 'leave', 'overtime', 'workforce_calendar',
                'announcements', 'messaging', 'notes', 'employee_documents',
                'shift_rosters', 'attendance_corrections', 'reports', 'payroll',
                'workplace_hub', 'audit_logs', 'automation', 'scheduled_reports',
                'integrations', 'sso_scim',
            ],
            'limits' => ['employees' => null],
        ],
    ],
];
