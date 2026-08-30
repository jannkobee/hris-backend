# SaaS, Multi-Tenancy, and Enterprise Roadmap

Assessment date: 2026-08-26

## Executive decision

The application has a substantial HRIS feature base, but it is currently a
single-company application. It must not onboard a second company until every
business query, validator, cache entry, file, queued job, scheduled task,
broadcast channel, and audit record is tenant-aware and covered by isolation
tests.

The recommended v1 architecture is:

| Concern | Recommended default |
| --- | --- |
| Data isolation | Shared MySQL database with an `organization_id` on every tenant-owned row |
| Tenant resolution | Verified subdomain, for example `acme.hris.example.com`; never trust an arbitrary tenant request header |
| User membership | One organization per user for v1, with `(organization_id, email)` unique |
| Authorization | Global permission catalog, organization-owned roles, and a separate platform-administration guard |
| Enterprise isolation | Offer a dedicated database later for customers that require it |
| Plans | Keep subscription entitlements separate from role permissions |

Multi-tenancy is the data-isolation architecture. SaaS is the wider product and
operating model: tenant onboarding, subscriptions, entitlements, quotas,
support, billing, observability, backups, exports, suspension, and offboarding.

The Basic and Enterprise controls in the role-permission dialog are role
presets only. They must not become the source of truth for SaaS plan access.

## Current product inventory

Implemented foundations:

- Capability-based roles and permissions
- Users, employee records, organization structure, job grades, and secure 201 files
- Attendance capture with photos, location, notes, and company-timezone handling
- Leave requests, balances, accrual rules, attachments, conversions, and approvals
- Overtime requests and approvals
- Payroll periods, calculations, work summaries, approval, payment status, payslips, and CSV export
- Dashboard, workforce calendar, announcements, audit logs, and application settings
- Direct/group messaging, private attachments, unread counts, and Reverb updates
- Scheduled tasks and leave-credit automation
- Workplace Hub rooms, collision checks, recurring meetings, minutes, decisions, files, and action items

These modules are real implementations rather than placeholders, but several
need production hardening before they can be sold as an enterprise service.

## P0: production and security gate

### 1. Close critical application risks

- Sanitize announcement HTML with a server-side allowlist before it reaches
  `v-html`. Add a restrictive Content Security Policy and move authentication
  toward secure, HttpOnly same-site cookies or short-lived, revocable tokens.
  Relevant files: `app/Http/Requests/AnnouncementRequest.php`,
  `src/components/RIchTextEditor.vue`, and
  `src/views/Modules/Dashboard.vue`.
- Add a dedicated `manage-leave-credits` permission. Leave-balance mutations
  must be ledger-based, require a reason, record an actor, and have endpoint
  authorization tests. Relevant file:
  `app/Http/Controllers/LeaveCredit/LeaveCreditController.php`.
- Replace stored raw Artisan command strings with a server-owned allowlist of
  task types and validated parameters. Relevant files:
  `app/Http/Requests/ScheduledTaskRequest.php`,
  `app/Http/Controllers/ScheduledTask/ScheduledTaskController.php`, and
  `app/Console/Kernel.php`.
- Replace the fixed `secret` password with an expiring invitation/password-set
  flow. Never run the known demo administrator seeder in production. Relevant
  files: `app/Repository/User/UserRepository.php` and
  `database/seeders/AdminSeeder.php`.
- Add login-specific throttling, generic credential errors, password reset,
  MFA, account verification/status, session/device management, and correct
  current-token revocation. Relevant files: `app/Http/Requests/LoginRequest.php`,
  `app/Services/Auth/AuthService.php`, and
  `app/Providers/RouteServiceProvider.php`.
- Protect immutable owner/system roles and the last organization owner. Replace
  the mutable role-name `Admin` bypass, and prevent role deletion from
  cascading into user deletion. Relevant files: `app/Models/User.php` and
  `database/migrations/2025_05_06_055052_change_is_admin_column_to_role_id_in_users_table.php`.
- Split employee-directory visibility from company-presence visibility and
  minimize the data returned to ordinary employees. Do not return drafts or
  future announcements from the employee feed.

### 2. Add database invariants and concurrency control

- Enforce one employee profile per user.
- Enforce tenant-scoped employee/date attendance uniqueness.
- Wrap employee and nested address/contact writes in one transaction.
- Lock payroll periods while processing, approving, paying, or rebuilding
  items; make approved/finalized calculation snapshots immutable.
- Add transition guards and idempotency to approval/payment endpoints.
- Replace destructive HR-record deletion with explicit lifecycle status,
  retention, and auditable archival where required.

### 3. Establish the tenant boundary

Use expand/backfill/contract migrations:

1. Create `organizations` with UUID, unique slug, legal/display name, timezone,
   status, and optional plan code.
2. Create a legacy organization and add nullable `organization_id` columns.
3. Backfill root records with the legacy ID and child records from their parent;
   report and repair orphans.
4. Replace global unique indexes with organization-composite indexes.
5. Add non-null constraints, organization foreign keys, and composite
   organization/resource foreign keys after the backfill is verified.

Keep `permissions` global. Tenant-own all business records, including dependent
and pivot rows: users, roles, audit logs, organization structure, employees and
their child records, attendance, all leave/overtime data, announcements,
settings, scheduled tasks, messages, payroll, Workplace Hub, and holidays.

Global uniqueness that must become tenant-relative includes user email,
department and role names, employment statuses, job-grade name/code, employee
number, leave type, scheduled-task name, application-setting key, holiday date,
meeting-room code, and position name.

### 4. Implement tenant-aware runtime services

- Add a request-scoped `TenantContext`, fail-closed organization scope, a
  `BelongsToOrganization` model concern, and host resolution before Sanctum
  authentication.
- Scope login by verified organization plus email and reject inactive
  organizations or host/token mismatches.
- Make route binding, manual lookups, raw SQL, imports, `exists` validation,
  and `unique` validation explicitly organization-aware.
- Prefix cache keys, locks, file paths, exports, broadcast channels, and rate
  limits with the organization ID.
- Serialize tenant context into queued work and reset it between jobs in
  long-running workers.
- Run scheduled tasks and leave accrual within an explicit organization
  context.
- Split seeders into global catalog seeding and idempotent per-organization
  provisioning.
- Use a same-origin, tenant-host-relative API in production and namespace
  browser-cached state by organization slug.

High-risk current paths include `app/Repository/Base/BaseRepository.php`,
`app/Services/Auth/AuthService.php`,
`app/Services/AppSettings/AppSettingService.php`,
`app/Http/Controllers/Dashboard/DashboardController.php`,
`app/Repository/Conversation/ConversationRepository.php`,
`app/Http/Controllers/Payroll/PayrollController.php`, and all file-download
controllers.

### 5. Require adversarial isolation tests

Provision Organization A and Organization B and verify for every resource:

- list endpoints never include the other organization's records;
- known foreign UUIDs return 404/403 for show, update, delete, download, and
  relationship assignment;
- duplicate tenant-relative natural keys are allowed across organizations;
- cache, storage, exports, notifications, jobs, scheduled work, and broadcasts
  cannot cross the boundary;
- platform administrators require an explicit, audited control-plane action
  and cannot silently inherit a tenant `Admin` role.

Tenant provisioning must remain disabled until this suite passes across every
module.

## P1: enterprise HR and Philippine payroll

### Payroll and statutory compliance

- Store effective-dated, versioned statutory rules instead of mutable global
  rates and hard-coded brackets.
- Add regular/special holiday, rest-day, overtime, and night-differential rule
  combinations; connect the workforce calendar to payroll.
- Add earning/deduction tax classes, 13th-month pay, annual tax reconciliation,
  refunds, final pay, retro/off-cycle payroll, loans, garnishments, and benefits.
- Generate and reconcile BIR 2316/1604-C and alphalist output, plus
  SSS/PhilHealth/Pag-IBIG remittance reports/files and filing evidence.
- Add maker-checker separation for processor, reviewer, approver, and payer;
  record `paid_by`, payment batch/reference, bank export, and reconciliation.

The current BIR periodic brackets are a useful base, but a configurable
calculator is not yet a complete compliant payroll engine. Official rules must
be verified whenever a statutory version is published.

### Time, leave, and overtime correctness

- Add locations, work schedules, shifts/rotas, rest days, break policies,
  geofences, device/import reconciliation, and attendance-correction approval
  history.
- Make leave duration schedule- and holiday-aware, support partial days
  consistently, prevent overlaps, add manager hierarchy/delegation, prevent
  self-approval, and support approved cancellation/reversal.
- Calculate leave-conversion value on the server from effective salary and
  policy; integrate approved conversions into payroll.
- Derive overtime duration on the server; validate cross-midnight work,
  overlaps, attendance, workday type, and locked approval transitions.

### Core HR operations

- Add legal entities, branches, locations, cost centers, reporting lines, and
  manager-scoped data access.
- Add effective-dated employment and compensation history.
- Add onboarding/offboarding checklists, probation/regularization, document
  expiry/versioning, equipment return, and final clearance.
- Build configurable multi-step approvals with delegates, escalation, SLA,
  comments, and email/in-app notifications.
- Add field-level salary/PII permissions, encrypted sensitive data, malware
  scanning/quarantine for uploads, retention schedules, subject export/delete
  workflows, and auditable file access.
- Add operational dashboards for headcount, attrition, attendance exceptions,
  leave liability, payroll variance, and compliance deadlines.

## P1: SaaS control plane

- Transactional, idempotent organization provisioning with an owner invitation,
  default roles/presets, settings, leave types, calendars, and branding.
- Subscription plans and feature entitlements enforced by the backend,
  independently of employee roles.
- Seats/storage/API quotas, trials, grace periods, suspension, renewal, and
  billing-provider webhooks with idempotency.
- Tenant-aware logs, metrics, support tooling, backups/restores, data export,
  retention, and offboarding deletion.
- Wildcard DNS/TLS, trusted-host enforcement, strict CORS/Reverb origins,
  secret management, disaster recovery, and tenant-specific incident tracing.

## P2: product expansion

- Performance goals, review cycles, calibration, and development plans
- Recruitment/applicant tracking and offer/onboarding conversion
- Training, licenses, certifications, and compliance expiry reminders
- Benefits enrollment, expenses/claims, assets, and employee help desk
- SSO/SAML, SCIM provisioning, API keys, webhooks, and integration marketplace
- Advanced analytics and scheduled management reports
- Workplace Hub RSVP/reminders, series-level changes, My Action Items, and
  calendar integration
- Messaging search, archive/retention, participant management, and notification
  preferences

These should follow tenant isolation, auth hardening, and payroll correctness;
adding more modules first would increase the number of unsafe paths that later
need conversion.

## Verification and release gates

- Restore backend database tests by enabling `pdo_sqlite` locally and in CI.
- Add frontend unit/component tests; the frontend currently has only build and
  lint scripts.
- Run authorization regression tests for every permission and role.
- Run cross-tenant tests on every deployment that changes queries, caching,
  storage, jobs, or WebSocket authorization.
- Require backup-restore drills, vulnerability scanning, audit-log export, and
  documented tenant offboarding before production SaaS launch.

## External reference anchors

- OWASP Multi-Tenant Security Cheat Sheet:
  https://cheatsheetseries.owasp.org/cheatsheets/Multi_Tenant_Security_Cheat_Sheet.html
- Philippine National Privacy Commission, Data Privacy Act implementing rules:
  https://privacy.gov.ph/implementing-rules-regulations-data-privacy-act-2012/
- BIR revised withholding table effective 2023 onward:
  https://bir-cdn.bir.gov.ph/local/pdf/Annex%20E%20RR%2011-2018.pdf
- SSS contribution guidance:
  https://www.sss.gov.ph/pay-contribution/
- PhilHealth employer guidance:
  https://www.philhealth.gov.ph/partners/employers/
