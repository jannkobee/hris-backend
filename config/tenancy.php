<?php

return [
    /*
     * In production, set this to the host suffix used by tenant APIs. For
     * example, "hris.example.com" resolves acme.hris.example.com to "acme".
     * When it is empty (the local-development default), every request uses
     * the configured default organization slug.
     */
    'base_domain' => env('TENANT_BASE_DOMAIN'),

    'default_slug' => env('TENANT_DEFAULT_SLUG', 'legacy'),
    'default_name' => env('TENANT_DEFAULT_NAME', 'Legacy Organization'),
    'default_timezone' => env('TENANT_DEFAULT_TIMEZONE', env('APP_TIMEZONE', 'UTC')),
    'default_country_code' => env('TENANT_DEFAULT_COUNTRY_CODE', 'PH'),

    // Applies only when this pre-SaaS installation is adopted as its first
    // organization. New tenants should use the plan selected at checkout.
    'legacy_plan' => env('TENANT_LEGACY_PLAN', 'enterprise'),

    /*
     * Canonical schema inventory for company-owned data. The tenancy:audit
     * command verifies that each table exists and carries organization_id.
     */
    'owned_tables' => [
        'users', 'roles', 'app_settings',
        'announcements', 'attendances', 'conversations', 'conversation_participants',
        'departments', 'employees', 'employee_addresses', 'employee_contacts',
        'employee_documents', 'employee_number_settings', 'employment_statuses',
        'holidays', 'job_grades', 'leave_types', 'leave_conversion_requests',
        'leave_credits', 'leave_credit_logs', 'leave_credit_settings', 'leave_requests',
        'leave_request_attachments', 'meeting_action_items', 'meeting_attachments',
        'meeting_attendees', 'meeting_rooms', 'messages', 'message_attachments',
        'notes', 'overtimes', 'payroll_items', 'payroll_periods', 'positions', 'saved_reports', 'scim_tokens', 'sso_configurations', 'statutory_rules', 'webhook_subscriptions',
        'scheduled_tasks', 'user_settings', 'workplace_meetings', 'audit_logs', 'subscription_events',
        'password_reset_requests', 'shift_templates', 'shift_assignments', 'attendance_correction_requests', 'app_notifications', 'leave_blackout_dates', 'approval_delegations', 'leave_credit_carryovers', 'organization_owner_invitations', 'organization_data_exports', 'employee_lifecycle_checklists', 'employee_lifecycle_tasks', 'approval_workflows', 'approval_workflow_steps', 'payroll_adjustment_runs', 'payroll_adjustment_items', 'statutory_report_runs', 'payslip_archives', 'performance_goals', 'performance_reviews', 'training_courses', 'training_enrollments', 'benefit_plans', 'benefit_enrollments', 'expense_claims',
    ],
];
