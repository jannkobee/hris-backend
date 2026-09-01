<?php

return [
    'disk' => env('ORGANIZATION_EXPORT_DISK', env('FILESYSTEM_DISK', 'local')),
    'retention_hours' => env('ORGANIZATION_EXPORT_RETENTION_HOURS', 72),
    'tables' => ['users', 'employees', 'departments', 'positions', 'employment_statuses', 'job_grades', 'leave_types', 'leave_credits', 'leave_requests', 'attendances', 'overtimes', 'payroll_periods', 'payroll_items', 'holidays', 'shift_templates', 'shift_assignments', 'announcements', 'notes', 'app_settings'],
    'excluded_columns' => ['default' => ['organization_id'], 'users' => ['organization_id', 'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'profile_photo_path', 'profile_photo_disk']],
];
