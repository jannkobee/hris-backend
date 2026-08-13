<?php

return [
    'catalog' => [
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
        ],
        'Attendance' => [
            'view-attendances' => ['View company attendance', 'View attendance records for all employees.'],
            'manage-attendances' => ['Manage attendance', 'Create, correct, and remove manual attendance records.'],
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
        'Organization Setup' => [
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
            'manage-app-settings' => ['Manage app settings', 'Change company-wide application policies.'],
        ],
    ],

    'default_roles' => [
        'User' => [
            'view-leave-requests',
            'create-leave-requests',
            'view-leave-conversion-requests',
            'create-leave-conversion-requests',
            'view-overtimes',
            'create-overtimes',
        ],
    ],
];
