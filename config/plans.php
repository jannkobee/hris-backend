<?php

return [
    'default' => env('DEFAULT_ORGANIZATION_PLAN', 'basic'),

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
    ],

    'plans' => [
        'basic' => [
            'name' => 'Basic',
            'description' => 'Core employee records and day-to-day HR self-service.',
            'features' => [
                'core_hr',
                'attendance',
                'leave',
                'overtime',
                'workforce_calendar',
                'announcements',
                'messaging',
                'notes',
            ],
            'limits' => ['employees' => 50],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'description' => 'Every available HRIS module and enterprise control.',
            'features' => ['*'],
            'limits' => ['employees' => null],
        ],
    ],
];
