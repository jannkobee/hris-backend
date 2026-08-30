<?php

$catalog = [
    'User Management' => [
        'view-users' => ['View users', 'Open the user directory and user details.'],
        'manage-users' => ['Manage users', 'Create, import, update, and deactivate user accounts.'],
    ],
    'Role Management' => [
        'view-roles' => ['View roles', 'Open roles and see their assigned permissions.'],
        'manage-roles' => ['Manage roles', 'Create, update, and remove roles.'],
        'manage-role-permissions' => ['Assign role permissions', 'Choose the capabilities assigned to each role.'],
    ],
    'Employee Management' => [
        'view-employees' => ['View employees', 'Open the employee directory and employment details.'],
        'manage-employees' => ['Manage employees', 'Create, update, import, and remove employee records.'],
        'manage-employee-number-settings' => ['Manage employee numbering', 'Configure and reformat employee number generation.'],
        'view-employee-documents' => ['View employee 201 files', 'View and download personnel documents for all employees.'],
        'manage-employee-documents' => ['Manage employee 201 files', 'Upload, update, and remove personnel documents for employees.'],
    ],
    'Payroll' => [
        'view-payroll' => ['View company payroll', 'View payroll periods and payslips for all employees.'],
        'manage-payroll' => ['Manage payroll', 'Create payroll periods and process employee pay.'],
        'approve-payroll' => ['Approve payroll', 'Approve processed payroll before disbursement.'],
        'mark-payroll-paid' => ['Record payroll payment', 'Mark an approved payroll period as paid.'],
    ],
    'Workplace Hub' => [
        'view-workplace-hub' => ['Use Workplace Hub', 'View rooms and meetings where the user is an organizer or attendee.'],
        'create-meetings' => ['Schedule meetings', 'Schedule meetings, invite coworkers, and collaborate on meeting records.'],
        'manage-company-meetings' => ['Manage company meetings', 'View and manage every meeting across the company.'],
        'manage-meeting-rooms' => ['Manage meeting rooms', 'Create, update, deactivate, and remove bookable rooms.'],
    ],
    'Attendance' => [
        'view-attendances' => ['View company attendance', 'View attendance records for all employees.'],
        'manage-attendances' => ['Manage attendance', 'Create, correct, and remove manual attendance records.'],
        'view-shifts' => ['View shifts and rosters', 'View shift templates and employee work schedules.'],
        'manage-shifts' => ['Manage shifts and rosters', 'Create shift templates and assign employee work schedules.'],
        'view-attendance-corrections' => ['View attendance corrections', 'View submitted attendance correction requests.'],
        'approve-attendance-corrections' => ['Approve attendance corrections', 'Approve or reject attendance correction requests.'],
    ],
    'Leave Requests' => [
        'view-leave-requests' => ['View leave requests', 'View permitted leave requests; employees remain limited to their own.'],
        'create-leave-requests' => ['Submit leave requests', 'Submit a leave request for the signed-in employee.'],
        'manage-leave-requests' => ['Manage leave requests', 'Correct pending leave requests for other employees.'],
        'approve-leave-requests' => ['Approve leave requests', 'Approve or reject pending leave requests.'],
    ],
    'Leave Balances' => [
        'view-leave-credits' => ['View leave balances', 'View employee leave balances.'],
    ],
    'Leave Conversions' => [
        'view-leave-conversion-requests' => ['View leave conversions', 'View permitted leave conversion requests.'],
        'create-leave-conversion-requests' => ['Submit leave conversions', 'Submit a leave conversion for the signed-in employee.'],
        'manage-leave-conversion-requests' => ['Manage leave conversions', 'Update or remove pending leave conversion requests.'],
        'approve-leave-conversion-requests' => ['Approve leave conversions', 'Approve or reject pending leave conversions.'],
    ],
    'Overtime' => [
        'view-overtimes' => ['View overtime requests', 'View permitted overtime requests.'],
        'create-overtimes' => ['Submit overtime requests', 'Submit overtime for the signed-in employee.'],
        'manage-overtimes' => ['Manage overtime requests', 'Update or remove pending overtime requests.'],
        'approve-overtimes' => ['Approve overtime requests', 'Approve or reject pending overtime requests.'],
    ],
    'Announcements' => [
        'view-announcements' => ['View announcement management', 'Open the announcement management page.'],
        'manage-announcements' => ['Manage announcements', 'Create, publish, update, and remove announcements.'],
    ],
    'Notes' => [
        'view-notes' => ['View personal notes', 'Open and search the signed-in user’s private notes.'],
        'manage-notes' => ['Manage personal notes', 'Create, update, archive, and remove the signed-in user’s private notes.'],
    ],
    'Organization Setup' => [
        'view-holidays' => ['View workforce calendar', 'View company holidays and special working days.'],
        'manage-holidays' => ['Manage workforce calendar', 'Create, update, and remove company holidays and special working days.'],
        'view-departments' => ['View departments', 'View the department directory.'],
        'manage-departments' => ['Manage departments', 'Create, update, and remove departments.'],
        'view-positions' => ['View positions', 'View configured positions.'],
        'manage-positions' => ['Manage positions', 'Create, update, and remove positions.'],
        'view-employment-statuses' => ['View employment statuses', 'View configured employment statuses.'],
        'manage-employment-statuses' => ['Manage employment statuses', 'Create, update, and remove employment statuses.'],
        'view-job-grades' => ['View job grades', 'View configured job grades.'],
        'manage-job-grades' => ['Manage job grades', 'Create, update, and remove job grades.'],
    ],
    'Leave Configuration' => [
        'view-leave-types' => ['View leave types', 'View configured leave types.'],
        'manage-leave-types' => ['Manage leave types', 'Create, update, and remove leave types.'],
        'view-leave-credit-settings' => ['View leave credit rules', 'View automatic leave credit rules.'],
        'manage-leave-credit-settings' => ['Manage leave credit rules', 'Create, update, and remove leave credit rules.'],
    ],
    'Scheduled Tasks' => [
        'view-scheduled-tasks' => ['View scheduled tasks', 'View automation schedules and their latest results.'],
        'manage-scheduled-tasks' => ['Manage scheduled tasks', 'Create, update, pause, and remove schedules.'],
        'run-scheduled-tasks' => ['Run scheduled tasks', 'Run an approved scheduled task immediately.'],
    ],
    'Audit and Settings' => [
        'view-audit-logs' => ['View audit logs', 'Search and inspect the company audit trail.'],
        'view-reports' => ['View reports', 'Run operational workforce and payroll reports.'],
        'manage-reports' => ['Manage reports', 'Create, update, and remove saved report definitions.'],
        'manage-app-settings' => ['Manage all app settings', 'Change every company-wide application policy.'],
        'manage-organization-settings' => ['Manage organization settings', 'Change the company name, timezone, and regional defaults.'],
        'manage-attendance-settings' => ['Manage attendance settings', 'Configure attendance photos, location, notes, IP capture, and manual entries.'],
        'manage-feature-settings' => ['Manage feature settings', 'Enable or configure shared features such as 201 files, leave attachments, messaging, and alerts.'],
        'manage-payroll-settings' => ['Manage payroll settings', 'Change payroll calculation and statutory contribution policies.'],
        'manage-integrations' => ['Manage integrations', 'Create and revoke API tokens and webhook subscriptions.'],
        'manage-sso' => ['Manage SSO and SCIM', 'Configure enterprise sign-in and provisioning credentials.'],
    ],
];

$catalogPermissionSlugs = [];

foreach ($catalog as $permissions) {
    $catalogPermissionSlugs = [
        ...$catalogPermissionSlugs,
        ...array_keys($permissions),
    ];
}

$employeeSelfServicePermissionSlugs = [
    'view-holidays',
    'view-announcements',
    'view-notes',
    'manage-notes',
    'view-workplace-hub',
    'create-meetings',
    'view-leave-requests',
    'create-leave-requests',
    'view-leave-credits',
    'view-leave-conversion-requests',
    'create-leave-conversion-requests',
    'view-overtimes',
    'create-overtimes',
];

return [
    'catalog' => $catalog,

    /*
     * Role templates are editable starting points for a company's roles.
     * Subscription plans decide which product capabilities a company owns;
     * these templates only decide what a role may do within those capabilities.
     */
    'role_templates' => [
        'administrator' => [
            'name' => 'Administrator',
            'description' => 'Full access to every permission in the catalog, including security, configuration, payroll, and audit tools.',
            'icon' => 'mdi-shield-crown-outline',
            'color' => 'primary',
            'permission_slugs' => $catalogPermissionSlugs,
        ],
        'hr-manager' => [
            'name' => 'HR Manager',
            'description' => 'Runs employee records, attendance, leave, organization setup, announcements, and core HR settings without payroll or security administration.',
            'icon' => 'mdi-account-tie-outline',
            'color' => 'secondary',
            'permission_slugs' => array_values(array_unique([
                ...$employeeSelfServicePermissionSlugs,
                'view-users',
                'manage-users',
                'view-roles',
                'view-employees',
                'manage-employees',
                'manage-employee-number-settings',
                'view-employee-documents',
                'manage-employee-documents',
                'view-attendances',
                'manage-attendances',
                'view-shifts',
                'manage-shifts',
                'manage-leave-requests',
                'approve-leave-requests',
                'manage-leave-conversion-requests',
                'approve-leave-conversion-requests',
                'manage-overtimes',
                'approve-overtimes',
                'manage-announcements',
                'manage-holidays',
                'view-departments',
                'manage-departments',
                'view-positions',
                'manage-positions',
                'view-employment-statuses',
                'manage-employment-statuses',
                'view-job-grades',
                'manage-job-grades',
                'view-leave-types',
                'manage-leave-types',
                'view-leave-credit-settings',
                'manage-leave-credit-settings',
                'view-audit-logs',
                'manage-organization-settings',
                'manage-attendance-settings',
                'manage-feature-settings',
            ])),
        ],
        'manager-approver' => [
            'name' => 'Manager / Approver',
            'description' => 'Handles employee self-service and reviews company leave, leave conversion, and overtime requests.',
            'icon' => 'mdi-account-check-outline',
            'color' => 'info',
            'permission_slugs' => array_values(array_unique([
                ...$employeeSelfServicePermissionSlugs,
                'view-employees',
                'view-attendances',
                'view-shifts',
                'view-leave-types',
                'approve-leave-requests',
                'approve-leave-conversion-requests',
                'approve-overtimes',
            ])),
        ],
        'payroll-specialist' => [
            'name' => 'Payroll Specialist',
            'description' => 'Prepares payroll using employee, attendance, leave, overtime, holiday, and job data without approving or marking payroll as paid.',
            'icon' => 'mdi-cash-multiple',
            'color' => 'success',
            'permission_slugs' => array_values(array_unique([
                ...$employeeSelfServicePermissionSlugs,
                'view-employees',
                'view-payroll',
                'manage-payroll',
                'view-attendances',
                'view-leave-types',
                'view-employment-statuses',
                'view-job-grades',
                'manage-payroll-settings',
            ])),
        ],
        'auditor-read-only' => [
            'name' => 'Auditor / Read-only',
            'description' => 'Reviews company records, payroll, configuration, and audit history without create, update, approval, or payment permissions.',
            'icon' => 'mdi-file-search-outline',
            'color' => 'warning',
            'permission_slugs' => array_values(array_filter(
                $catalogPermissionSlugs,
                static fn (string $slug): bool => str_starts_with($slug, 'view-')
            )),
        ],
        'employee-self-service' => [
            'name' => 'Employee Self-Service',
            'description' => 'Uses the workforce calendar, announcements, meetings, leave balances and requests, leave conversions, and overtime for the signed-in employee.',
            'icon' => 'mdi-account-outline',
            'color' => 'teal',
            'permission_slugs' => $employeeSelfServicePermissionSlugs,
        ],
    ],

    'default_roles' => [
        'User' => $employeeSelfServicePermissionSlugs,
    ],
];
