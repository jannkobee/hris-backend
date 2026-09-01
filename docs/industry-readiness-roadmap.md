# HRIS Industry Readiness Roadmap

**Prepared:** 2026-08-31  
**Target:** Make HRISFlow a credible, enterprise-ready SaaS HRIS within 12-18 months  
**Baseline:** Solid MVP with multi-tenancy, core HR modules, payroll foundation, and strong UI/UX

---

## Executive Summary

Your HRIS has a real foundation: multi-tenant architecture, working HR modules (attendance, leave, overtime, payroll), dashboard, approvals, and messaging. The gap to enterprise readiness is not breadth of features—it is depth, security, compliance, and operational maturity.

**The path forward has five prioritized phases** that build on each other:
1. **Security & Tenancy** (foundation)
2. **SaaS Platform Ops** (business model)
3. **Workforce Lifecycle & Approvals** (HR competence)
4. **Payroll Compliance** (legal/trust)
5. **Advanced Features & Integration** (enterprise competitive advantage)

**Critical gate:** Do not add second tenant, go to production, or sell until Phase 1 is complete.

## Current Implementation Audit

**Audited:** 2026-08-31  
**Method:** Repository evidence from routes, migrations, models, services, controllers, frontend views, and automated tests. “Implemented” means a working code path exists; it does not replace production security review, payment-provider testing, load testing, or legal validation.

**Verification note (2026-09-01):** SQLite PDO is enabled and the full automated suite passes: **134 tests, 1,292 assertions**. The MySQL migration set was also applied successfully and `php artisan tenancy:audit` passes for all 69 tenant-owned tables. SCIM provisioning was verified without requiring an identity provider to supply a birthday; the user field is now nullable for external provisioning while ordinary HR profiles can still collect it.

**Delivery standard:** Every roadmap implementation must add or update focused automated tests (including authorization and tenant-isolation coverage where applicable), run code formatting, run the relevant test suite, run `php artisan tenancy:audit` after tenant-schema changes, and update this roadmap with the implemented scope, verification result, and remaining gaps.

**Demo-data verification (2026-09-01):** `php artisan industry:seed-demo legacy` has been run successfully. It is idempotent and provides visible sample records for the employee profile, attendance, leave, overtime, workforce calendar, announcements, performance, training, benefits, and both submitted and reimbursed expenses.

### Status legend

- **Implemented:** The main end-to-end capability exists in the current codebase.
- **Partial:** A usable foundation exists, but one or more roadmap acceptance criteria remain.
- **Missing:** No meaningful implementation was found beyond adjacent infrastructure or display copy.

### Phase 1 audit — Security and tenancy

| Workstream | Status | Existing evidence | Remaining work |
| --- | --- | --- | --- |
| 1.1 Tenant isolation | **Implemented foundation** | `BelongsToOrganization`, `TenantContext`, `TenantRule`, `tenancy:audit`, existing isolation tests, and `AdversarialTenantIsolationTest` covering resource lists, hostile foreign UUIDs, relationship injection, settings caches, private files, payroll exports, natural keys, and queued leave-credit work | Extend the matrix to broadcasts and every newly introduced resource/command; enable database-backed execution in CI and make it a required check. |
| 1.2 Authentication | **Mostly implemented** | Tenant-aware password reset, generic response, reset/session revocation, MFA/TOTP and recovery codes, MFA login challenge, session listing/revocation, login throttling, inactive-user rejection, frontend reset/MFA/session UI, `AuthSecurityTest` | Align reset expiry with the 15-minute target if desired; add richer device fingerprints, exponential lockout/alerts, full status enum/soft-delete lifecycle, recovery-code download, and security-cookie deployment validation. |
| 1.3 Authorization | **Implemented foundation** | Permission middleware, controller resource permissions, tenant middleware/rules, route guards/navigation filtering, plan middleware, privileged payroll/document/correction/import route hardening, and strict `authorization:audit` coverage for every authenticated tenant API endpoint | Add database-backed role/permission regression cases in CI for each policy boundary and review the registry whenever a controller workflow changes. |
| 1.4 Audit compliance | **Implemented foundation** | Expanded audit payload/context, mutation and workflow logging, immutable `AuditLog` records, retention metadata, a tenant-scoped tamper-evident hash chain, compliance CSV export, and scheduled `audit-logs:verify` integrity verification | Add external/archive storage for records whose retention window ends, audit summary metrics, and database-backed regression coverage in CI. |
| 1.5 Secrets and encryption | **Implemented foundation** | Environment-based provider secrets; encrypted MFA credentials, OIDC client secret, webhook signing secret, employee-document identifier and notes; hashed Sanctum/SCIM credentials; `security:encryption-audit`; key-rotation runbook | Add fields only after a data-classification review, keep production secrets in a managed secret store, and execute/rehearse the documented re-encryption procedure before any `APP_KEY` rotation. |
| 1.6 CSP and HTML safety | **Implemented foundation** | Server-side `HtmlSanitizer`, announcement write sanitization, defensive frontend sanitization, configurable CSP/security headers, Vite development headers, and focused sanitizer/header tests | Validate the CSP against the production proxy and all deployed integrations, migrate or resave legacy rich text if needed, and keep XSS regression cases in CI. |
| 1.7 Security operations | **Implemented foundation** | Backend/frontend CI security workflows, strict authorization/encryption checks, `SECURITY.md`, key-rotation runbook, incident-response checklist, and focused security tests | Add SAST, dependency-alert triage, a formal threat model, production monitoring/alerting, OWASP review, and an independent penetration test before launch. |

**Phase 1 conclusion:** The authentication, HTML-safety, and tenant-isolation foundations are substantially ahead of the original baseline, but Phase 1 is **not complete**. Comprehensive authorization coverage, audit retention/export, encryption review, CI enforcement, and security operations remain launch gates.

### Phase 2 audit — SaaS platform operations

| Workstream | Status | Existing evidence | Remaining work |
| --- | --- | --- | --- |
| 2.1 Organization onboarding | **Implemented foundation** | Provisioning API/service, platform console organization list/create/detail, public trial signup, country/timezone/plan setup, owner invitation creation/acceptance email flow, and transactional provisioning tests | Add resend/revoke UI, verified custom domains, and production subdomain/TLS validation. |
| 2.2 Billing and subscriptions | **Advanced foundation** | Region-aware plans, Stripe checkout service, signed Stripe webhook endpoint, subscription fields/events, lifecycle reconciliation, trials, entitlement and employee-limit enforcement, console billing operations, and owner-only customer billing portal sessions | Add payment-method/invoice UX, upgrade/downgrade scheduling and proration validation, dunning communications, and live Stripe end-to-end tests. |
| 2.3 Billing webhook idempotency | **Implemented foundation** | Signature validation, persisted subscription events, provider event IDs, reconciliation service | Expand provider-event test matrix, replay/operations UI, alerting, and production webhook observability. |
| 2.4 Suspension/reactivation | **Mostly implemented** | Platform status API/UI, subscription reconciliation, tenant middleware enforcement, credential revocation controls | Finalize user-facing suspension/grace messaging, email communication, and comprehensive lifecycle tests. |
| 2.5 Export and offboarding | **Implemented foundation** | Owner-only asynchronous whole-tenant JSON export request, private storage, expiry/checksum metadata, credential/secret exclusion, tenant-scoped download, and export audit events; organization offboarding metadata is ready for a non-destructive workflow | Add email-ready notices, scheduled file cleanup, explicit offboarding request/review/retention workflow, backups, and restore testing. Legal retention/deletion rules must be approved before any automatic deletion is enabled. |
| 2.6 Platform health/support | **Implemented foundation** | Dimmed platform console, overview metrics, organization detail, subscription/identity/webhook visibility, `PlatformSupportService`, and a provisioning-key-protected platform health endpoint covering database, cache, private storage, queue driver, and organization status totals | Add mail delivery/job failure metrics, alert thresholds, impersonation with consent/banner/audit, maintenance controls, and external monitoring integration. |

### Phase 3 audit — Workforce lifecycle and approvals

| Workstream | Status | Existing evidence | Remaining work |
| --- | --- | --- | --- |
| 3.1 Structure/reporting lines | **In progress** | Departments, positions, grades, and employee assignments exist; an Artisan-generated migration now introduces tenant-safe manager references and effective employment dates | Wire validation, hierarchy APIs, org-chart UI, and a full effective-dated employment-history domain. |
| 3.2 Onboarding/offboarding | **Implemented foundation** | Tenant-scoped onboarding/offboarding checklists, task owners/dates, employee assignment, completion accountability, lifecycle API routes, and audit events | Add checklist templates/copying, asset/access tasks, reminders, probation/regularization, employee-facing UI, and lifecycle timeline. |
| 3.3 Approval workflows | **Implemented foundation** | Approval inbox, persistent notifications, leave/overtime/correction decisions, delegated approvers with expiry, configurable workflow/ordered-step models and management API, conditions/SLA metadata, and a next-approver resolver | Connect resolver to each decision workflow, add parallel routing/escalations/UI, and add database-backed delegation/escalation tests. |
| 3.4 Documents | **Partial** | Tenant-scoped employee document upload/download UI and plan gating | Versioning, expiry, retention, access/download log completeness, approval states, reminders, and expiry dashboard. |
| 3.5 Custom employee fields | **Missing** | Dynamic form infrastructure exists but no field-definition/value domain | Definition/value models, validation and encryption flags, admin builder, employee rendering, and reporting integration. |

### Phase 4 audit — Payroll compliance

| Workstream | Status | Existing evidence | Remaining work |
| --- | --- | --- | --- |
| 4.1 Locking/versioning | **Mostly implemented** | Lock endpoint, immutable locked periods, calculation and locked snapshots, payroll exception acknowledgement, detailed payroll UI | Separate adjustment-run model/workflow, version chain, adjustment payslips, and dedicated adjustment UI. |
| 4.2 Statutory rules | **Mostly implemented** | Tenant-scoped effective-dated `StatutoryRule`, resolver, calculator integration, CRUD API, payroll Settings UI | Prevent overlapping rule periods explicitly, add import/seed version governance, broader historical calculation tests, and legal review of rule content. |
| 4.3 Reconciliation/variance | **Missing** | Payroll register and CSV export are adjacent foundations | Previous-period variance engine, reconciliation checklist, bank-file import/matching, sign-off, and exception workflow. |
| 4.4 Statutory reporting | **Missing** | Philippine contribution/tax calculations exist | Formal BIR 1601-C/2316/alphalist, SSS, PhilHealth, Pag-IBIG exports, validation, archival, and filing-status UI. |
| 4.5 Payslips | **Partial to advanced** | Employee-scoped payslip retrieval, snapshot-backed detail, printable UI, earnings/deduction breakdown | Server-generated PDF, immutable PDF archival/download, email delivery, branding, and access audit completion. |
| 4.6 Maker-checker | **Implemented foundation** | Process/approve/lock/paid states and audit fields; maker-checker enforcement now blocks a payroll creator from approving, locking, or paying their own period and blocks the approver from marking it paid | Define dedicated finance roles, rejection/reopen workflow, optional threshold approvals, and database-backed maker-checker tests. |

### Phase 5 audit — Advanced capabilities

| Workstream | Status | Existing evidence | Remaining work |
| --- | --- | --- | --- |
| 5.1 Reporting/analytics | **Advanced foundation** | Operational reports, filters, CSV export, saved reports, scheduled delivery command/UI, dashboard, and role-aware default dashboard layouts with tenant-scoped user widget preferences | Add visualization builder, additional report domains, delivery history, and scale/performance tests. |
| 5.2 APIs/webhooks | **Implemented foundation** | Scoped Sanctum API-token management, tenant-owned signed webhook subscriptions, delivery tracking, employee events, Integrations UI | Expand event catalogue, retry/dead-letter processing, delivery log UI, IP restrictions, rate-limit controls, docs, and test console. |
| 5.3 Enterprise identity | **Partial to advanced** | OIDC discovery/login/token validation and UI; SCIM bearer tokens and user CRUD/patch; encrypted OIDC secret; plan gating and tests | SAML 2.0, group-to-role mapping, broader SCIM filters/groups/bulk behavior, IdP setup guides, and certificate rotation. |
| 5.4 Performance/goals | **Implemented MVP foundation** | Tenant-scoped goals and performance-review records, validated list/create/finalize APIs, 1–5 ratings, feedback, review cycle/status, and audit events | Add employee self-service visibility, goal progress updates, calibration, review templates, reporting, and frontend workflows. |
| 5.5 Training/certification | **Implemented MVP foundation** | Tenant-scoped course catalog, employee enrollment/completion, certificate-expiry fields, audited APIs, and a daily tenant-safe in-app expiry-reminder command | Add employee-facing UI, role assignment, certificate files, email reminders, and reporting. |
| 5.6 Benefits/expenses | **Implemented backend MVP** | Tenant-scoped benefit plans/enrollments with contribution amounts automatically deducted per payroll frequency and stored on payroll items; expense submission, manager review/rejection, finance reimbursement reference/date, audit events, authorization, and workflow tests | Add private receipt upload, employee-facing Benefits/Expenses UI, enrollment approvals, expense reporting, and reimbursement export. |
| 5.7 Marketplace | **Missing** | Generic API/webhook foundation and holiday provider integration exist | Provider framework, OAuth credential lifecycle, sync logs, health/retry controls, and packaged Slack/Teams/Google integrations. |

### Revised priority from the audit

1. **Finish the Phase 1 launch gate:** add SAST/dependency-alert triage, a formal threat model, production monitoring, OWASP review, and an independent penetration test. HTML sanitization/CSP, tenant isolation, authorization coverage, immutable audit export, encryption checks, CI, and security documentation are now implemented foundations.
2. **Complete SaaS customer lifecycle:** owner invitations, customer billing portal and dunning, tenant export/offboarding, backups/restore, and operational health monitoring.
3. **Build organizational hierarchy and employee onboarding/offboarding.** These are now the largest core-HRIS workflow gaps.
4. **Replace single-step approvals with a configurable multi-step workflow engine**, reusing the existing inbox, delegation, and notifications.
5. **Close payroll compliance gaps:** adjustment runs, reconciliation, statutory filing exports, archived PDF payslips, and enforced maker-checker separation.
6. **Only then add talent modules** such as performance, training, benefits, and expenses.

### Recommended immediate build block

The next implementation block is **operational health/support**, followed by organizational hierarchy and employee lifecycle. The Phase 1 engineering foundations are implemented, while external validation and production operations remain launch requirements.

---

## Phase 1: Security & Tenancy Hardening (Weeks 1–8)

**Goal:** Build the security and isolation foundation that enterprise customers require.

**Business value:** Credible enough to handle real customer data; no obvious security risks.

### 1.1 Multi-tenant isolation tests and coverage

**Status:** Some isolation tests exist; coverage incomplete.  
**Effort:** 2–3 weeks  
**Owner:** Backend engineer

**Deliverables:**
- [ ] Adversarial isolation test suite: for every resource (employees, attendance, leave, payroll, documents, messages, settings, scheduled tasks, webhooks, audit logs), verify:
  - List endpoints never leak Organization B records when logged in as Organization A
  - Known foreign UUIDs return 403/404 for show, update, delete, download, relationship assignment
  - Duplicate tenant-relative natural keys (department name, role name, leave type, etc.) are allowed across organizations
  - Cache, file storage, exports, broadcasts, and queued jobs cannot cross tenant boundaries
  - All child records inherit organization scope from parent (e.g., employee → addresses, contacts, documents, leave requests)
- [ ] Test coverage for all scheduled commands and background jobs (leave accrual, payroll processing, notification delivery, report scheduling)
- [ ] Test suite for file uploads, downloads, and exports by organization
- [ ] Regression tests for every permission + role combination across two simultaneous organizations

**Acceptance criteria:**
- All adversarial tests pass
- New feature tests must include isolation verification
- CI pipeline runs isolation suite on every commit

**Implementation notes:**
- Use `TenantContext` to run tests under two organizations simultaneously
- Verify broadcast channels use organization-scoped namespace
- Check cache keys prefix with `organization_id`
- Verify file paths and export filenames don't leak organization data

---

### 1.2 Authentication hardening

**Status:** Basic login works; password reset, MFA, session management are incomplete.  
**Effort:** 3–4 weeks  
**Owner:** Backend engineer + frontend engineer

**Deliverables:**

#### Backend
- [ ] Hardened password reset flow:
  - Implement `PasswordResetRequest` with expiring token (15 min), one-time use
  - Do not reveal whether email exists in organization (generic response)
  - Require token + new password + password confirmation
  - Invalidate all sessions after successful reset
  - Send reset link via email with secure random token
  - Rate limit password reset attempts (5 per hour per email, 20 per hour per IP)

- [ ] Multi-factor authentication (TOTP):
  - Support optional user-level MFA (TOTP, e.g., Google Authenticator)
  - Generate and validate TOTP secrets
  - Provide recovery codes (10x one-time use)
  - Require MFA at login if enabled, after password entry
  - Allow disable and regenerate actions

- [ ] Session and device management:
  - Track sessions and device fingerprints
  - Return session ID in auth response
  - Allow "logout from all devices"
  - Invalidate sessions on password change, role change, or user deactivation
  - Set secure, HttpOnly, SameSite cookies for sensitive tokens

- [ ] Login throttling and brute-force protection:
  - Rate limit login attempts (5 failures per email per 15 min, then exponential backoff)
  - Generic error response ("Invalid email or password") to prevent user enumeration
  - Log failed login attempts for security audit
  - Trigger alert or lock user after N failures

- [ ] Account deactivation and status:
  - Add `status` enum (active, inactive, suspended, deleted) to User model
  - Reject login for inactive/suspended users
  - Allow admin to deactivate users
  - Soft-delete users (set status = deleted, keep data)

#### Frontend
- [ ] Password reset form:
  - Link from login page to `/reset-password` with token query param
  - Validate form: new password, confirm password, strength indicator
  - Show errors clearly (token expired, invalid, mismatch)

- [ ] MFA setup flow:
  - Settings page option to enable MFA
  - Display QR code and manual entry key
  - Verify TOTP code to confirm setup
  - Show recovery codes (download and copy options)
  - Warning: "If you lose these codes and can't use TOTP, contact support"

- [ ] Login with MFA:
  - After password entry, show "Enter code from authenticator" form
  - Input field for 6-digit code
  - "Lost access to authenticator?" link → recovery code entry

**Acceptance criteria:**
- Password reset form is secure and cannot reveal user existence
- MFA is optional per user and fully functional
- Logout from all devices works; sessions expire correctly
- Brute-force throttling prevents rapid login attempts
- No plaintext passwords in logs or error messages

**Implementation notes:**
- Use `Laravel\Fortify` or custom guards; prefer custom to control tenant context
- TOTP: Use `spatie/laravel-qrcode` and `pragmarx/google2fa` or similar
- Store MFA secret encrypted in DB
- Update frontend axios instance to handle 403 "MFA required" responses
- Document password policy (length, complexity) in settings

---

### 1.3 Authorization audit and enforcement

**Status:** Permissions exist; some endpoints missing authorization checks.  
**Effort:** 2–3 weeks  
**Owner:** Backend engineer

**Deliverables:**
- [ ] Audit every API endpoint:
  - Does it check a permission or role?
  - Does it scope the query to the authenticated user's organization?
  - Does it validate input belongs to the correct organization (TenantRule)?
  - Does it reject foreign UUIDs with 403/404?
  - Is there a feature test that verifies the permission is enforced?

- [ ] Add missing authorization:
  - `manage-users` → user CRUD, deactivation, role assignment
  - `manage-roles` → role CRUD, permission assignment
  - `manage-leave-credits` → leave balance mutations (accrual, adjustment, manual entry)
  - `manage-payroll` → payroll period lock/unlock, payment approval, recalculation
  - `manage-audit-logs` → audit log access, export
  - `manage-settings` → organization settings, integrations
  - `manage-documents` → sensitive employee documents, retention policies

- [ ] Frontend authorization guard:
  - Every route meta declares required `permission` and optional `planFeature`
  - Router navigation guard validates current user's permissions before rendering
  - Hide navigation items and action buttons when permission is missing
  - Show permission-denied state in dialogs/forms when user lacks permission

- [ ] Systematic feature test for every permission:
  - Create test users with different role/permission combinations
  - Verify allowed actions succeed
  - Verify denied actions return 403
  - Verify 403 response does not leak data (no "record not found" vs "access denied" timing)

**Acceptance criteria:**
- Every endpoint has explicit authorization
- Feature tests cover happy path, permission denied, and feature gating
- Frontend routes and buttons respect permissions
- CI runs permission regression tests
- No endpoint should accidentally grant access

**Implementation notes:**
- Update `EnsurePlanFeature` middleware to reject with 403 before controller runs
- Use blade/vue `@can` directive consistently
- Document permission matrix by module in AGENTS.md
- Create permission audit script: `php artisan auth:audit-endpoints`

---

### 1.4 Audit logging and compliance

**Status:** AuditLog model exists; logging is selective.  
**Effort:** 2 weeks  
**Owner:** Backend engineer

**Deliverables:**
- [ ] Expand audit logging:
  - Log all user creations, deactivations, role/permission changes
  - Log all payroll period state changes (draft → locked → approved → paid)
  - Log all leave/overtime approval state changes
  - Log all sensitive setting changes (company name, payroll country, etc.)
  - Log all file uploads and downloads (employee documents, payroll exports)
  - Log all failed login attempts and permission violations
  - Log all paid data mutations (employee records, leave balances, payroll items)

- [ ] Audit log retention:
  - Immutable audit log (append-only; no edits/deletes except expiry policy)
  - Configurable retention (e.g., 7 years for payroll, 2 years for access logs)
  - Scheduled cleanup job to delete/archive expired logs
  - Export endpoint for compliance (e.g., BIR audit downloads)

- [ ] Audit log details:
  - Record actor (user ID, name, role), action (create, update, delete, view, download), resource type, resource ID, before/after values (for mutations)
  - Device info (IP, user-agent, device fingerprint if available)
  - Timestamp with microsecond precision
  - Organization ID always scoped

- [ ] Audit log export:
  - CSV export for admin/compliance officer
  - Filter by date range, user, action, resource type
  - Tamper-evident (hash or signed export)
  - Rate-limited to prevent data exfiltration

**Acceptance criteria:**
- All sensitive actions are logged
- Audit log is immutable
- Retention policy is enforced
- Export format supports regulatory review
- Dashboard shows audit summary (actions per day, permission denials, failed logins)

**Implementation notes:**
- Use Laravel events to trigger audit logs
- Create `LogSensitiveAction` observer or middleware
- Audit log should never be deleted by normal operations; only expiry policy
- Document audit log schema in data dictionary

---

### 1.5 Secrets and encryption

**Status:** Config env vars used; no encryption at rest for sensitive data.  
**Effort:** 1–2 weeks  
**Owner:** Backend engineer

**Deliverables:**
- [ ] Encrypted sensitive fields:
  - OIDC client secret, SAML cert/key
  - SCIM token
  - Stripe API key
  - Any external API credentials stored in AppSetting or SsoConfiguration
  - Employee salary (in payroll module)

- [ ] Use Laravel's encryption:
  - Enable transparent encryption for model fields using `Encryptable` or custom mutator
  - Store cipher in config; rotate cipher key with deployment
  - Decrypt only when needed (not in list endpoints)

- [ ] Secrets management (post-Phase 1):
  - Environment variables for production secrets (OIDC, Stripe, mail)
  - Consider external secret manager (AWS Secrets Manager, HashiCorp Vault) for future
  - Never commit `.env` with real values
  - Rotate Stripe/OIDC credentials quarterly

**Acceptance criteria:**
- Sensitive fields are encrypted at rest
- Decryption key is environment-based, never in code
- No plaintext secrets in logs or error messages
- Secret rotation documented and tested

---

### 1.6 Content Security Policy and HTML sanitization

**Status:** Some concerns flagged in roadmap docs.  
**Effort:** 1 week  
**Owner:** Backend engineer + frontend engineer

**Deliverables:**
- [ ] HTML sanitization:
  - Sanitize announcement HTML on server before saving
  - Use allowlist of safe HTML tags: `<p>`, `<br>`, `<strong>`, `<em>`, `<ul>`, `<ol>`, `<li>`, `<a href="...">`
  - Strip `<script>`, `<iframe>`, `onclick`, `onerror`, etc.
  - Use `HTMLPurifier` or `mews/purifier` package

- [ ] Content Security Policy:
  - Set header `Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-...'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'`
  - Disable `unsafe-inline` for scripts (use nonce)
  - Allow external resources only if needed (e.g., Gravatar)

- [ ] Frontend:
  - Replace `v-html` with `v-text` where possible
  - If HTML is unavoidable, use sanitized version only after server-side sanitization
  - Avoid `innerHTML` in JavaScript

**Acceptance criteria:**
- Announcement HTML is sanitized
- CSP header is set and enforced in browser
- XSS tests pass (try `<script>alert('xss')</script>` in announcement)
- No `unsafe-inline` or `unsafe-eval` in CSP

---

### 1.7 Security testing and hardening

**Status:** Basic build/lint tests exist; security tests sparse.  
**Effort:** 1–2 weeks  
**Owner:** DevOps/security engineer

**Deliverables:**
- [ ] OWASP Top 10 checklist:
  - SQL injection: use parameterized queries, Laravel ORM (done)
  - Authentication: MFA, strong password, session management (Phase 1)
  - Sensitive data exposure: encryption, HTTPS only (Phase 1)
  - XML external entities: no XML parsing (N/A)
  - Broken access control: authorization tests (Phase 1)
  - Security misconfiguration: secure headers, no debug mode in prod (1.7)
  - Cross-site scripting: sanitization, CSP (1.6)
  - Insecure deserialization: avoid `unserialize()`, use JSON (check code)
  - Using components with known vulnerabilities: `composer audit`, `npm audit` (1.7)
  - Insufficient logging: audit logs (1.4)

- [ ] Automated security scanning:
  - Run `composer audit` and `npm audit` in CI/CD
  - Optional: SAST scanner (SonarQube, Snyk, or GitHub CodeQL)
  - Fail CI if high/critical vulnerabilities found

- [ ] Penetration testing plan:
  - Document scope and assumptions
  - Plan for Q1 of next year: manual pentesting or bug bounty
  - Fix findings before production launch

**Acceptance criteria:**
- OWASP checklist reviewed; findings documented
- No high/critical vulnerabilities in dependency audit
- Security testing integrated into CI/CD
- Pentesting plan approved and scheduled

**Implementation notes:**
- Create `SECURITY.md` with vulnerability disclosure policy
- Set up dependency update alerts (Dependabot, GitHub)
- Document security assumptions and threat model

---

### Phase 1 Acceptance Criteria

**Gate: Do NOT proceed to Phase 2 until all of Phase 1 is complete.**

- [ ] Isolation test suite passes; new features require isolation tests
- [ ] Password reset, MFA, session management all working
- [ ] Every endpoint has explicit authorization; feature tests for all permissions
- [ ] Sensitive actions logged; audit log immutable and exported
- [ ] Sensitive fields encrypted; no plaintext secrets in code
- [ ] Announcement HTML sanitized; CSP header set
- [ ] OWASP checklist reviewed; dependency audit clean
- [ ] Security documentation (threat model, incident response, pentesting plan) complete
- [ ] Team trained on secure coding practices

---

## Phase 2: SaaS Platform Operations (Weeks 9–18)

**Goal:** Build the business model and operational infrastructure for multi-tenant SaaS.

**Business value:** Move from "strong app" to "credible SaaS business"; enable paid subscriptions and support multiple customers.

### 2.1 Organization onboarding and activation flow

**Status:** Manual artisan command; no UI.  
**Effort:** 3–4 weeks  
**Owner:** Backend engineer + frontend engineer

**Deliverables:**

#### Backend
- [ ] Organization provisioning API:
  - `POST /platform/organizations` → create org, owner user, default roles, settings, leave types, holidays
  - Input: company name, slug, timezone, country, plan code, owner email, owner full name
  - Output: organization ID, subdomain, admin invitation link
  - Idempotent: re-running with same slug does not duplicate
  - Transactional: all-or-nothing (org + owner + roles + settings or rollback)

- [ ] Owner invitation flow:
  - Generate secure invitation token (random, 32 bytes, expires in 7 days)
  - Send invitation email with `https://acme.hris.example.com/accept-invite?token=...`
  - Invitation token can be used to create password and activate account
  - One-time use: after acceptance, token is invalidated
  - Invitation can be resent or revoked by platform admin

- [ ] Tenant domain setup:
  - Support subdomain (e.g., `acme.hris.example.com`)
  - Support custom domain (e.g., `hr.acme.com`) after verification
  - Verify custom domain ownership via DNS TXT or CNAME record
  - Update `ResolveTenant` middleware to resolve by hostname

#### Frontend
- [ ] Platform admin panel → organizations list:
  - Show all organizations with status, plan, created date, owner email
  - Search/filter by name, slug, status
  - Create new organization form
  - Organization detail view with edit, suspend, reactivate, delete actions

- [ ] Organization creation flow:
  - Form: company name, slug, timezone, country, plan, owner email, owner name
  - Validate slug uniqueness and format
  - Show confirmation and success message with invitation link
  - Display invitation link and "Send via email" button

- [ ] Invitation acceptance flow:
  - Page `/accept-invite?token=...`
  - Show "Welcome to [Company]! Set up your account."
  - Form: full name (pre-filled if available), email (pre-filled), password, password confirm
  - After submit, redirect to login
  - Handle invalid/expired token with clear error

**Acceptance criteria:**
- Organization can be created via API and UI
- Owner receives invitation and accepts it
- New organization is isolated and functional
- Subdomain resolution works
- Custom domain setup documented

**Implementation notes:**
- Slug format: lowercase, alphanumeric + hyphen, 3-50 chars, unique
- Invitation email template with branding and support link
- Store invitation token in `password_reset_requests` or separate `invitation_tokens` table
- Verify SSL wildcard cert or configure TLS for custom domains (infrastructure task)

---

### 2.2 Subscription lifecycle and billing integration

**Status:** Plan metadata exists; no billing, checkout, or webhook handling.  
**Effort:** 4–6 weeks  
**Owner:** Backend engineer (Stripe integration expert preferred)

**Deliverables:**

#### Backend
- [ ] Stripe integration:
  - Create Stripe account and API keys
  - `POST /platform/organizations/{id}/checkout-session` → return Stripe checkout session URL
  - `POST /webhooks/stripe` → handle payment_intent.succeeded, customer.subscription.updated, customer.subscription.deleted, invoice.payment_failed
  - Validate webhook signature; reject unsigned webhooks
  - Idempotent webhook processing (if Stripe resends, use `idempotency_key` in request)

- [ ] Subscription model and states:
  - Add `SubscriptionEvent` table: org_id, event_type, event_data, stripe_event_id, created_at
  - Track subscription status: trialing, active, past_due, suspended, cancelled
  - Store Stripe subscription ID and customer ID on Organization
  - On webhook, update organization subscription_status and log event

- [ ] Plan entitlements enforcement:
  - Define plans in config/plans.php: Basic (employees, attendance, leave, overtime), Enterprise (+ payroll, workplace hub, notes, custom fields)
  - Store plan code on Organization
  - Backend middleware `EnsurePlanFeature` checks organization.plan against required feature
  - Fail with 403 if plan does not include feature

- [ ] Trial and grace periods:
  - Add `trial_starts_at`, `trial_ends_at`, `grace_until` to Organization
  - New organizations start 14-day trial automatically
  - After trial, require active subscription to access paid features
  - Grace period (3 days) after payment failure before suspension

- [ ] Usage quota enforcement:
  - Track monthly active users, storage, API calls per organization
  - Store quotas by plan in config
  - Reject user invitations if over seat limit
  - Warn when approaching quota
  - (Post-Phase 2: implement hard limits)

#### Frontend
- [ ] Billing portal:
  - Settings → Billing tab
  - Show current plan, subscription status, renewal date
  - Button "Change plan" → Stripe checkout
  - Button "Manage billing" → Stripe customer portal
  - Show usage (users, storage)

- [ ] Plan upgrade/downgrade:
  - Present plan options (Basic vs Enterprise)
  - Show price difference and proration explanation
  - Redirect to Stripe checkout on selection
  - After success, redirect to billing page

- [ ] Payment failure notifications:
  - Toast notification: "Payment failed. Manage billing to update payment method."
  - Link to Stripe customer portal or `/billing/update-payment`
  - Show grace period countdown ("Plan will suspend in 2 days")
  - Persist notification until resolved

- [ ] Feature gating:
  - Hide payroll, workplace hub, notes, custom fields for Basic plan
  - Show "Upgrade to Enterprise to use this feature" tooltip/modal

**Acceptance criteria:**
- Stripe integration is complete and tested
- Trial → paid conversion works end-to-end
- Webhook handling is idempotent and error-safe
- Plan entitlements are enforced in backend and frontend
- Usage is tracked and limits are enforced (soft limits in Phase 2)
- Payment failure flow is clear and actionable

**Implementation notes:**
- Test mode with Stripe test keys first
- Mock Stripe in tests or use `mockstripe/mockstripe` package
- Webhook must log and retry failed events (queue job)
- Currency: use organization.country or USD global default
- Tax calculation: consider TaxJar or similar for international customers
- Invoice generation: use Stripe invoices or generate PDF from Organization + subscription data

---

### 2.3 Subscription event webhooks and idempotency

**Status:** Webhook structure exists; incomplete.  
**Effort:** 1–2 weeks  
**Owner:** Backend engineer

**Deliverables:**
- [ ] Webhook receiver:
  - Verify Stripe signature on every webhook
  - Log webhook receipt and processing
  - Idempotent processing: check if event already processed (store stripe_event_id)
  - Retry on transient errors; skip after 3 failed retries

- [ ] Event handlers:
  - `payment_intent.succeeded` → update subscription status to active, clear past_due flag
  - `customer.subscription.updated` → check new plan vs old plan; update organization, trigger notification if plan changed
  - `customer.subscription.deleted` → set subscription_status to cancelled, disable organization (or set grace_until)
  - `invoice.payment_failed` → increment payment_failure_count, set past_due flag, set grace_until = now + 3 days

- [ ] Notification on subscription events:
  - Send email: "Subscription updated to [plan]" or "Payment failed; plan will suspend in 3 days"
  - In-app notification: "Upgrade available" or "Payment action required"
  - Link to billing portal or update-payment flow

**Acceptance criteria:**
- All Stripe webhook events are handled
- Webhook processing is idempotent
- Signature verification prevents tampering
- Events trigger appropriate notifications

---

### 2.4 Tenant suspension and reactivation

**Status:** No workflows.  
**Effort:** 1–2 weeks  
**Owner:** Backend engineer + frontend engineer

**Deliverables:**

#### Backend
- [ ] Suspension workflow:
  - Automatic: subscription_status becomes `suspended` OR grace period expires
  - Manual: platform admin action "Suspend organization"
  - Set `suspended_at` and `suspension_reason` on Organization
  - On suspension: block all tenant login, show "Organization suspended" message
  - Preserve all data; do not delete

- [ ] Reactivation workflow:
  - Admin action "Reactivate organization"
  - Requires subscription to be active (not past_due or cancelled)
  - Clear `suspended_at`
  - Send email to owner: "Organization reactivated"
  - Users can log in again

#### Frontend
- [ ] Organization detail (admin):
  - Show "SUSPENDED" badge if suspended
  - Button "Reactivate" (if conditions met)
  - Button "Suspend" (for testing; requires confirmation)
  - Show suspension reason and date

- [ ] Tenant app (user view):
  - On login, if organization is suspended, show modal: "Your organization has been suspended. Contact support."
  - No access to app until reactivated

**Acceptance criteria:**
- Suspended organizations cannot be accessed
- Reactivation works smoothly
- Data is preserved during suspension
- Users are notified appropriately

---

### 2.5 Tenant data export and offboarding

**Status:** No workflows; data is in app only.  
**Effort:** 2–3 weeks  
**Owner:** Backend engineer

**Deliverables:**
- [ ] Data export:
  - `POST /platform/organizations/{id}/export` → queue job to export all org data
  - Export includes: employees, departments, positions, roles, users, attendance, leave, overtime, payroll, documents, audit logs, settings, announcements
  - Format: JSON or CSV per entity type, in a ZIP file
  - Store export file in `storage/exports/`, signed download URL
  - Access log export download

- [ ] Data retention and deletion:
  - On subscription cancelled or admin action, set deletion policy
  - Soft delete: set status = deleted, keep data for 90 days, then hard-delete
  - Hard delete: immediately delete all organization data
  - Audit trail of deletion request: who, when, reason

- [ ] Backup and restore:
  - Daily backups (infrastructure layer)
  - Restore capability documented and tested quarterly
  - Recovery time objective (RTO) and recovery point objective (RPO) defined

**Acceptance criteria:**
- Organizations can export their data
- Deletion follows 90-day retention with soft-delete
- Backup/restore tested and documented

**Implementation notes:**
- Export job should be queued and run asynchronously
- Large exports may take time; email download link when ready
- Document compliance with GDPR/privacy act for data subject requests
- Consider S3 for export storage instead of local filesystem

---

### 2.6 Platform support and health dashboard

**Status:** No admin dashboard.  
**Effort:** 2 weeks  
**Owner:** Frontend engineer + backend engineer

**Deliverables:**

#### Backend
- [ ] Health metrics:
  - Count of organizations by status (active, trialing, suspended)
  - Count of users per organization (average, total)
  - Monthly recurring revenue (MRR) and churn
  - Storage used per organization
  - Login success/failure rates

#### Frontend
- [ ] Platform admin dashboard:
  - Overview: total orgs, active users, MRR, churn rate
  - Organizations table: name, plan, status, users, created, last login
  - Organization detail view (already started in 2.1)
  - Search and filter by status, plan, date range
  - Quick actions: suspend, reactivate, force password reset (support)

- [ ] Support tools:
  - "Switch to organization" (admin can view org as tenant user for debugging)
  - Reset organization admin password (send reset link)
  - View organization's audit log
  - Send announcement to specific organizations

**Acceptance criteria:**
- Admin can view and manage organizations
- Metrics are available and accurate
- Support tools allow debugging without direct DB access

---

### Phase 2 Acceptance Criteria

**Gate: Do NOT proceed to Phase 3 until Phases 1 and 2 are complete.**

- [ ] Organization onboarding flow works end-to-end
- [ ] Owner receives and accepts invitation
- [ ] Stripe integration is complete; test checkout works
- [ ] Subscription lifecycle (trial → active → past_due → suspended) is implemented
- [ ] All Stripe webhooks are handled idempotently
- [ ] Plan entitlements are enforced in backend and frontend
- [ ] Suspension and reactivation workflows work
- [ ] Data export and retention policies are documented and tested
- [ ] Admin dashboard is functional
- [ ] Prod deployment checklist prepared (domains, TLS, backups, monitoring)

---

## Phase 3: Workforce Lifecycle & Approval Workflows (Weeks 19–34)

**Goal:** Deepen HR functionality to support real workforce management and configurable approvals.

**Business value:** Becomes usable by mid-size companies with complex HR workflows; differentiator vs. payroll-only tools.

### 3.1 Organizational structure and reporting lines

**Status:** Department, position, job grade exist; no org chart or manager hierarchy.  
**Effort:** 2–3 weeks  
**Owner:** Backend engineer + frontend engineer

**Deliverables:**

#### Backend
- [ ] Employee hierarchy:
  - Add `manager_id` (nullable UUID) to Employee, foreign key to users.id
  - Add scope `whereManager($userId)` to get all direct reports
  - Recursive query: get all subordinates (multi-level reports)
  - Add scope `whereManagerPath()` to check if user is in the chain of command

- [ ] Organizational unit:
  - Add `Location`, `CostCenter`, `BusinessUnit` models (optional, depending on customer needs)
  - Link employees to these organizational units
  - Support hierarchical structure (location → department → team)

- [ ] Effective-dated employment history:
  - Add `EmploymentHistory` table: employee_id, start_date, end_date, department_id, position_id, manager_id
  - Track employment changes: promotion, transfer, manager change
  - Default to most recent active record for current data
  - Support retroactive updates (backdate changes)

#### Frontend
- [ ] Org chart view:
  - Tree view showing org structure from top leader down
  - Click employee → detail view with reports, history
  - Filter by department or team
  - Edit reporting lines (drag-and-drop or form)

- [ ] Employee profile:
  - Show current manager, direct reports
  - Show employment history timeline

**Acceptance criteria:**
- Manager-subordinate relationship is tracked
- Org chart displays correctly
- Employment history is maintained
- Reporting line changes are audited

---

### 3.2 Employee lifecycle: onboarding and offboarding

**Status:** Basic employee record exists; no onboarding/offboarding workflows.  
**Effort:** 3–4 weeks  
**Owner:** Backend engineer + frontend engineer

**Deliverables:**

#### Backend
- [ ] Onboarding workflow:
  - Add `OnboardingChecklist` model: employee_id, task (e.g., "Create email account", "Set up IT equipment", "Conduct orientation"), assigned_to, completed_at
  - Predefined checklist template by role/department
  - Support custom checklists
  - Track progress: show % complete
  - Notification when task is due or overdue

- [ ] Offboarding workflow:
  - Add `OffboardingChecklist` model: employee_id, task (e.g., "Collect laptop", "Return keys", "Deactivate accounts"), assigned_to, completed_at
  - Triggered on employee status change to "inactive" or "terminated"
  - Checklist must be 100% complete before employee record is fully archived
  - Prevent employee data deletion until checklist complete

- [ ] Probation and regularization:
  - Add `probation_start_date`, `probation_end_date`, `regularization_date` to Employee
  - Track probation status: probationary, regularized, terminated-probation
  - Automation: on `probation_end_date`, send reminder to manager for regularization decision
  - Block leave/overtime approval during probation (configurable by policy)

#### Frontend
- [ ] Onboarding/offboarding list:
  - Dashboard view: open tasks, overdue tasks, progress by employee
  - Detail view: employee name, start date, checklist progress, notes
  - Check off tasks, add comments, upload attachments

- [ ] Employee status timeline:
  - Show lifecycle events: hired, regularized, promoted, suspended, terminated
  - Each event shows date, change reason, notes, actor

**Acceptance criteria:**
- Onboarding checklists can be created and tracked
- Offboarding prevents access and deletion until complete
- Probation status is enforced in workflows

---

### 3.3 Configurable multi-step approval workflows

**Status:** Approvals exist for leave, overtime; hardcoded flow.  
**Effort:** 4–5 weeks  
**Owner:** Backend engineer

**Deliverables:**

#### Backend
- [ ] Approval workflow engine:
  - Define workflow as a directed graph: step → approver → next step
  - Steps: draft, pending_approval, approved, rejected, cancelled
  - Approver: by role, by manager, by specific user, by job grade threshold

  - Model `ApprovalWorkflow`: organization_id, name (e.g., "Leave Request - Standard"), entity_type (leave_request, overtime_request, attendance_correction, payroll_adjustment)
  - Model `ApprovalStep`: workflow_id, sequence, approver_type (role, manager, user), approver_id, auto_approve_after_days (SLA)
  - Support multi-level approval: step 1 (team lead) → step 2 (manager) → step 3 (HR)

- [ ] Approval engine:
  - When entity (LeaveRequest, OvertimeRequest) is submitted, transition to step 1
  - Generate ApprovalTask for approver(s) in step 1
  - On approval, move to step 2; notify step 2 approver
  - On rejection, go back to draft; notify requester
  - Track approval history: who, when, comment, decision

- [ ] Delegation:
  - User can delegate approvals to another user for a date range
  - `ApprovalDelegation`: delegator_id, delegate_id, starts_on, ends_on, workflows (empty = all)
  - When approver is delegator, check for active delegation
  - If delegated, create task for delegate instead; note delegation in audit log

- [ ] Escalation (SLA):
  - If approval not completed after `auto_approve_after_days`, auto-approve or escalate to manager
  - Notification: "Approval overdue; auto-approving in 2 days"
  - Configurable per step

- [ ] Approval notifications:
  - Email: "Approval needed for [entity] by [requester]. Action needed."
  - In-app notification: linked to approval detail
  - Link to approval page with one-click approve/reject

#### Frontend
- [ ] Admin: approval workflow builder:
  - Settings → Approval Workflows
  - Create workflow: name, entity type, add steps
  - Per step: approver type (role, manager, user, threshold), SLA, auto-action
  - Drag to reorder steps
  - Save and activate

- [ ] Approval inbox (already exists; enhance):
  - Group approvals by workflow/entity type
  - Show pending count and SLA status
  - Detail modal: requester info, entity data, approval history, comment field
  - Approve/reject buttons
  - Delegate link

- [ ] Request detail (leave, overtime, etc.):
  - Show approval status with timeline
  - Show current approver and SLA deadline
  - If rejected, show reason and edit form to resubmit

**Acceptance criteria:**
- Workflows can be defined and activated
- Multi-step approvals work correctly
- Delegation and escalation are functional
- Notifications are timely and actionable
- Approval history is complete and auditable

**Implementation notes:**
- Use state machine library (e.g., `winzou/state-machine-bundle` or custom)
- ApprovalTask should be transient (created on submission, updated/deleted on decision)
- Test matrix: multi-user, multi-step, rejection, escalation, delegation, concurrent approvals
- Ensure no single approval blocks system (escalation after SLA)

---

### 3.4 Employee documents and lifecycle

**Status:** Employee documents exist; no lifecycle management, version history, or expiry.  
**Effort:** 2–3 weeks  
**Owner:** Backend engineer + frontend engineer

**Deliverables:**

#### Backend
- [ ] Document versioning:
  - Add `EmployeeDocumentVersion` table: document_id, version_number, file_path, mime_type, size, uploaded_by, uploaded_at
  - Store multiple versions of same document (e.g., insurance_cert_v1, v2, v3)
  - Default to latest version; allow viewing/downloading older versions
  - Soft-delete old versions; hard-delete after retention expires

- [ ] Document lifecycle:
  - Add `expires_at`, `expiry_warning_days`, `retention_years` to EmployeeDocument
  - Automation: N days before expiry, send notification "Document expires on [date]"
  - After expiry: flag as expired, still accessible but marked
  - After retention period: delete per policy
  - Compliance: document retention audit trail

- [ ] Document access log:
  - Log every document download/view: user, document, timestamp, IP
  - Access logs cannot be deleted (append-only); retained per policy
  - Export access log for compliance

- [ ] Document approval (optional feature for sensitive docs):
  - Add `approved_by`, `approved_at` to EmployeeDocument
  - Require HR approval before employee can upload sensitive documents (passport, visa, etc.)
  - Approval workflow: employee uploads → HR reviews → approve/reject with comment

#### Frontend
- [ ] Document management UI (already exists; enhance):
  - Show version history: list of previous versions
  - Download any version
  - Document expiry status: show "Expires in 30 days" badge
  - Add renewal reminder
  - Delete version option (only if retention allows)

- [ ] Expiry dashboard:
  - HR module: show documents expiring soon (30, 60, 90 days)
  - Export list for bulk renewal/follow-up
  - Set renewal date and send to employee

**Acceptance criteria:**
- Documents have version history
- Expiry and retention policies are enforced
- Access logs are complete and immutable
- Notifications for expiring documents work

---

### 3.5 Custom employee fields

**Status:** No dynamic custom fields; schema is fixed.  
**Effort:** 2–3 weeks  
**Owner:** Backend engineer + frontend engineer

**Deliverables:**

#### Backend
- [ ] Custom field definition:
  - Add `CustomEmployeeField` model: organization_id, name, field_type (text, textarea, select, date, number, checkbox), required, position, options (for select)
  - Support organization-specific custom fields
  - Tenant-scoped: each org has independent custom field schema

- [ ] Custom field values:
  - Add `EmployeeCustomFieldValue` table: employee_id, custom_field_id, value
  - Store value as JSON (supports different types)
  - Validate by field_type and required flag

#### Frontend
- [ ] Custom field admin:
  - Settings → Custom Employee Fields
  - Add field: name, type, required, options
  - Drag to reorder
  - Edit, delete, archive fields

- [ ] Employee form:
  - Display custom fields in form after standard fields
  - Validation per field_type
  - Save custom field values alongside standard employee data

**Acceptance criteria:**
- Custom fields can be defined per organization
- Employee form includes custom fields
- Custom field data is validated and stored correctly

---

### Phase 3 Acceptance Criteria

- [ ] Organizational structure (manager hierarchy) is implemented
- [ ] Onboarding and offboarding checklists work end-to-end
- [ ] Probation and regularization status is tracked
- [ ] Approval workflow builder allows configurable multi-step workflows
- [ ] Delegation and escalation (SLA) are functional
- [ ] Approval notifications are timely
- [ ] Document versioning and lifecycle management work
- [ ] Document expiry reminders and retention policies are enforced
- [ ] Custom employee fields can be defined and used
- [ ] Feature tests cover happy path and edge cases (rejection, escalation, delegation)

---

## Phase 4: Payroll Compliance & Financial Controls (Weeks 35–52)

**Goal:** Harden payroll to be production-grade, compliant, and auditable for enterprise customers.

**Business value:** Move payroll from "calculator" to "trusted financial system"; unlock enterprise contracts.

### 4.1 Payroll versioning and immutability

**Status:** Payroll periods and items exist; can be modified/deleted.  
**Effort:** 2–3 weeks  
**Owner:** Backend engineer

**Deliverables:**

#### Backend
- [ ] Payroll period locking:
  - Add `locked_at`, `locked_by` to PayrollPeriod
  - Once locked, period cannot be edited; only adjustments allowed in new adjustment run
  - Transition: draft → generating → generated → approving → approved → locked → paid
  - Lock automatically after payment confirmation (or manual action by finance)

- [ ] Payroll period snapshots:
  - On approval, create immutable snapshot of all PayrollItems for that period
  - Store snapshot as JSON in `calculation_snapshot` column
  - Snapshot includes: employee, gross, deductions, net, tax, all detail
  - Recalculation (after lock) creates new period or adjustment run, does not modify original

- [ ] Adjustment runs:
  - Add `PayrollAdjustmentRun` model: period_id, adjustment_type (bonus, deduction, correction), created_by, approved_by, status
  - Adjustments are separate from regular payroll items
  - Support retroactive pay: run for past period without affecting original calculation
  - Audit trail: original amount → adjustment → new total

#### Frontend
- [ ] Payroll period detail:
  - Show lock status: "Draft", "Locked", "Paid"
  - Lock button (finance role only): "Lock this period (cannot be edited)"
  - Confirmation dialog: "This period will be locked. You will not be able to edit it."
  - Show locked_at and locked_by

- [ ] Adjustment run UI:
  - New adjustment: select period, type, affected employees, amount/reason
  - Show original calculation vs. adjusted total
  - Submit for approval
  - Paid separately or included in next payroll

**Acceptance criteria:**
- Payroll periods can be locked
- Locked periods cannot be edited
- Adjustment runs are separate and auditable
- Snapshots preserve original calculations

---

### 4.2 Versioned statutory rules

**Status:** BIR tables are hardcoded in config; no versioning.  
**Effort:** 3–4 weeks  
**Owner:** Backend engineer + compliance expert

**Deliverables:**

#### Backend
- [ ] Statutory rule model:
  - Add `StatutoryRule` model: organization_id, country_code, rule_type (income_tax_bracket, sse_rate, philhealth_rate, pag_ibig_rate), effective_date, values (JSON)
  - Store versions: e.g., BIR 2023 rates, BIR 2024 rates
  - Payroll calculation queries the active rule for each period's date

- [ ] Rule management:
  - Admin interface to add/update rules by effective date
  - Rules are immutable once created (append-only history)
  - Support bulk import from PDF/spreadsheet
  - Version tagging: "BIR 2023", "SSS 2024-Q1", etc.

- [ ] Payroll calculation integration:
  - Payroll job queries active rule set for payroll_start_date
  - Calculate tax, SSS, PhilHealth, Pag-IBIG using rules[payroll_start_date]
  - Audit trail: which rule version was used for each calculation

#### Frontend
- [ ] Settings → Payroll → Statutory Rules:
  - List rules by type and effective date
  - Upload new rule: file (JSON/CSV), effective date, version name
  - Validate: check format, consistency, no gaps
  - Preview: show impact on sample payroll

**Acceptance criteria:**
- Statutory rules are versioned by effective date
- Payroll calculations use correct rule version for each period
- Rules can be imported and validated
- Audit trail shows which rule version was applied

**Implementation notes:**
- Reference BIR official brackets: https://bir-cdn.bir.gov.ph/
- Test against known payroll scenarios with 2023 and 2024 rates
- Document rule schema (columns, calculations)
- Provide test fixtures for regression testing

---

### 4.3 Payroll reconciliation and variance reporting

**Status:** Payroll reports exist; no reconciliation or variance detection.  
**Effort:** 2–3 weeks  
**Owner:** Backend engineer + finance team

**Deliverables:**

#### Backend
- [ ] Payroll variance report:
  - Compare period-to-period or month-to-month: total payroll, tax, deductions
  - Flag high variance (>5% or absolute threshold)
  - Drill down: by employee, by deduction type, by department
  - Export CSV

- [ ] Reconciliation checklist:
  - Add `PayrollReconciliation` model: period_id, reconciled_by, reconciled_at, notes, variance_status (ok, minor, major)
  - Finance officer checks: payroll total matches bank export, headcount matches, outliers explained
  - Sign-off: once reconciled, period is confirmed and data is locked further (for audit)

- [ ] Bank export matching:
  - Import bank file (CSV with payment records): employee ID/name, amount, date
  - Match to approved payroll: is every employee paid the correct amount on the correct date?
  - Report unmatched records (payments to non-employees, mismatches)
  - Audit trail: import date, imported by, matches

#### Frontend
- [ ] Payroll → Reconciliation:
  - List periods with reconciliation status
  - Detail: variance report, notes, sign-off button
  - Import bank file: upload CSV, preview matches, confirm
  - Export reconciliation report

**Acceptance criteria:**
- Variance reports can be generated
- Payroll can be reconciled and signed off
- Bank file matching works
- Reconciliation audit trail is complete

---

### 4.4 Statutory reporting and compliance

**Status:** No statutory reporting; payroll data exported manually.  
**Effort:** 3–4 weeks  
**Owner:** Backend engineer + compliance expert

**Deliverables:**

#### Backend
- [ ] BIR reporting:
  - Generate BIR 2316 (Annual Withholding Tax Report per employee): per-employee tax summary for calendar year
  - Generate Alphalist (BIR Form): all employees, gross, withholding tax, summary
  - Support BIR XML or CSV export format
  - Aggregate by organization for bulk filing

- [ ] SSS reporting:
  - Generate SSS contribution file: employee ID, name, salary, contributions
  - Generate remittance report: monthly total contributions to submit
  - Track remittance date and reference number

- [ ] PhilHealth and Pag-IBIG:
  - Generate remittance file and report per insurer
  - Track remittance status and confirmation

- [ ] Report generation and archival:
  - `POST /payroll/statutory-reports/generate` with year/month range
  - Async job generates reports, stores as files, sends download link
  - Archive reports: cannot be regenerated (immutable); re-download from archive
  - Audit log: who generated, when, what parameters

#### Frontend
- [ ] Settings → Compliance → Statutory Reports:
  - Generate report: select year, report type (BIR, SSS, PhilHealth, Pag-IBIG)
  - Download generated reports
  - View archive of past reports
  - Set remittance dates and track status

**Acceptance criteria:**
- BIR, SSS, PhilHealth, Pag-IBIG reports can be generated
- Reports are formatted per official specs
- Report generation is auditable
- Archive is immutable

**Implementation notes:**
- Reference official BIR forms: https://bir.gov.ph/
- Test with sample employee data and known calculations
- Document validation rules (all employees included, no duplicates, correct formulas)
- Consider third-party compliance service (e.g., BIR e-filing) for future integration

---

### 4.5 Payslips and employee pay statements

**Status:** Payroll items exist; no employee-facing payslip.  
**Effort:** 2 weeks  
**Owner:** Backend engineer + frontend engineer

**Deliverables:**

#### Backend
- [ ] Payslip generation:
  - On payroll approval/payment, generate Payslip PDF for each employee
  - Include: period dates, gross, earnings, deductions, net, tax, YTD totals
  - Store payslip file: `storage/payslips/{organization_id}/{year}/{month}/{employee_id}.pdf`
  - Track payslip access: who viewed, when

- [ ] Payslip retrieval:
  - `GET /my/payslips?year=2024&month=08` → list payslips for current user
  - `GET /payroll/payslips/{payslip_id}/download` → download PDF
  - Only employee, their manager, and finance can access

#### Frontend
- [ ] Employee payroll portal:
  - New section: "My Payslips"
  - Table: date, period, net pay, download button
  - Filter by year/month
  - Download PDF

- [ ] Payslip detail (PDF):
  - Company header (logo, name, address)
  - Period dates and pay date
  - Earnings: salary, overtime, bonuses, etc.
  - Deductions: tax, SSS, PhilHealth, Pag-IBIG, loans, etc.
  - Summary: gross, total deductions, net
  - YTD totals and tax summary

**Acceptance criteria:**
- Payslips are generated after payroll approval
- Employees can view and download payslips
- Access is logged and restricted to authorized users
- PDF format is clear and professional

---

### 4.6 Maker-checker controls and approval

**Status:** Single approver; no segregation of duties.  
**Effort:** 2 weeks  
**Owner:** Backend engineer

**Deliverables:**

#### Backend
- [ ] Segregation of duties:
  - Define roles: Payroll Processor (creates run), Payroll Reviewer (verifies), Payroll Approver (approves payment), Payroll Payer (executes payment)
  - Require different users for each role
  - Enforce: processor ≠ reviewer ≠ approver ≠ payer

- [ ] Payroll workflow:
  - Draft: processor creates period, generates items
  - Pending review: processor marks "ready for review"
  - Reviewed: reviewer checks calculations, reconciliation; approves or rejects with comment
  - Approved: approver final sign-off; triggers payment (or manual payment later)
  - Paid: payer confirms payment sent; marks as paid with reference number

- [ ] Audit trail:
  - Log each transition: user, timestamp, action (generate, submit for review, review approved, approve, pay)
  - Preserve comments and rejections

#### Frontend
- [ ] Payroll workflow UI:
  - Processor: "Submit for review" button
  - Reviewer: "Review results" page with variance checks, sign-off button
  - Approver: "Approve payroll" button
  - Payer: "Mark as paid" with reference number

**Acceptance criteria:**
- Roles are segregated
- Workflow transitions are enforced
- Audit trail shows each step and actor

---

### Phase 4 Acceptance Criteria

- [ ] Payroll periods can be locked; locked periods are immutable
- [ ] Adjustment runs are separate and auditable
- [ ] Statutory rules are versioned by effective date
- [ ] Payroll calculations use correct rule version
- [ ] Variance reports can be generated
- [ ] Reconciliation is supported; bank matching works
- [ ] BIR, SSS, PhilHealth, Pag-IBIG reports can be generated
- [ ] Payslips are generated and accessible to employees
- [ ] Maker-checker workflow is enforced
- [ ] Audit trail is complete for all payroll changes

---

## Phase 5: Advanced Features & Integration Layer (Months 13–18)

**Goal:** Add enterprise-grade reporting, integrations, and advanced HR modules.

**Business value:** Competitive advantage; unlock large enterprise deals; create ecosystem through APIs.

### 5.1 Advanced reporting and analytics

**Status:** Dashboard and reports exist; no saved reports, scheduling, or role-based dashboards.  
**Effort:** 3 weeks  
**Owner:** Backend engineer + frontend engineer

**Deliverables:**

#### Backend
- [ ] Saved reports:
  - Add `SavedReport` model: organization_id, name, report_type, filters (JSON), created_by, next_delivery_at, delivery_schedule
  - Support filters: date range, department, employee status, etc.
  - Allow editing filters and re-running

- [ ] Scheduled delivery:
  - Delivery schedule: daily, weekly (day of week), monthly (day of month)
  - On schedule, generate report and email to recipients
  - Track delivery history and failures

- [ ] Report types (expand existing):
  - Headcount report: total, by department, by status, trends
  - Attendance report: present/absent/late, by employee/department
  - Leave usage: used/remaining by type, by employee, by department
  - Payroll report: total payroll, tax, by department
  - Compliance report: auditable actions, approval SLAs, exceptions

#### Frontend
- [ ] Reports → Saved Reports:
  - List saved reports
  - Create report: name, type, filters, delivery schedule
  - Edit filters and re-run
  - Download or view in browser
  - Email delivery: add recipients
  - Delete report

- [ ] Role-based dashboards:
  - CEO: headcount, payroll trend, attrition, revenue impact
  - HR Manager: pending approvals, expiring documents, exceptions, headcount by dept
  - Department Manager: team members, attendance, leave usage, performance
  - Finance: payroll variance, statutory compliance, bank reconciliation status
  - Employee: my payslips, my leave balance, my attendance record, my tasks

**Acceptance criteria:**
- Reports can be saved and scheduled
- Delivery works reliably
- Dashboards are role-specific and relevant

---

### 5.2 API keys and webhook subscriptions

**Status:** No programmatic API or webhooks for integrations.  
**Effort:** 2–3 weeks  
**Owner:** Backend engineer

**Deliverables:**

#### Backend
- [ ] API key management:
  - Add `ApiKey` model: organization_id, name, key (hashed), secret, scopes (space-separated), last_used_at, created_at
  - Generate key/secret pair; allow rotating secret
  - Scopes: employees:read, employees:write, payroll:read, attendance:read, leave:read, etc.
  - Rate limit by key: 1000 requests/hour per scope

- [ ] Webhook subscriptions:
  - Add `WebhookSubscription` model: organization_id, event_type (employee.created, leave_request.approved, payroll.generated), url, active, secret
  - Support events: employee lifecycle, leave/overtime approvals, payroll state changes, attendance corrections
  - Retry on failure: exponential backoff, max 5 retries
  - Signature verification: HMAC-SHA256 of event payload + secret

#### Frontend
- [ ] Settings → Integrations:
  - API keys section: list, create new, rotate secret, delete
  - Webhooks section: list, create new (event + URL), test webhook (send test payload), delete
  - Show recent webhook deliveries and failures

**Acceptance criteria:**
- API keys can be created and rotated
- Webhooks are delivered with correct signature
- Retries work and are logged

---

### 5.3 SSO/SAML and SCIM provisioning

**Status:** OIDC exists; SAML and SCIM are not implemented.  
**Effort:** 2–3 weeks  
**Owner:** Backend engineer (enterprise auth expert preferred)

**Deliverables:**

#### Backend
- [ ] SAML integration:
  - Support SAML 2.0 single sign-on
  - Allow tenant to upload IdP certificate and configure SSO endpoint
  - On SAML assertion, create/update user in tenant
  - Map SAML attributes to user fields (email, name, department)
  - Login flow: redirect to IdP, receive assertion, auto-login to tenant

- [ ] SCIM provisioning:
  - Implement SCIM 2.0 API (users endpoint)
  - Allow IdP (e.g., Azure AD, Okta) to provision/deprovision users via SCIM
  - Endpoints: `/scim/v2/Users` (list, create, update, delete)
  - Validate SCIM bearer token (stored in `ScimToken`)

#### Frontend
- [ ] Settings → Single Sign-On:
  - SSO configuration: method (OIDC, SAML), enable/disable
  - For SAML: upload IdP certificate, enter SSO URL, configure attribute mapping
  - For SCIM: generate SCIM token (display once), show SCIM endpoint URL
  - Test button: verify SAML/SCIM connectivity

**Acceptance criteria:**
- SAML authentication works end-to-end
- SCIM user provisioning works
- Both support attribute mapping and user deactivation

---

### 5.4 Performance management and goals

**Status:** Implemented MVP workflow; employee-facing UI and payroll benefit deductions remain.  
**Effort:** 3–4 weeks  
**Owner:** Backend engineer + frontend engineer

**Deliverables:**

#### Backend
- [ ] Performance goals:
  - Add `PerformanceGoal` model: employee_id, goal_name, description, start_date, end_date, status (draft, active, completed, archived)
  - Link goal to role/competency framework
  - Support SMART criteria (Specific, Measurable, Achievable, Relevant, Time-bound)

- [ ] Performance reviews:
  - Add `PerformanceReview` model: employee_id, reviewer_id, period, rating (1-5), feedback, status (draft, submitted, finalized)
  - Self-review and manager review workflow
  - Support peer reviews (multi-rater feedback)
  - Review schedule: annual, mid-year, quarterly

#### Frontend
- [ ] My Goals (employee):
  - List active goals
  - Detail: description, progress, comments
  - Submit self-assessment

- [ ] Performance Reviews (manager):
  - List direct reports due for review
  - Review form: rating, feedback, goal alignment
  - Submit and finalize review
  - View past reviews

**Acceptance criteria:**
- Goals can be set and tracked
- Reviews can be submitted and stored
- Review workflow is enforced

---

### 5.5 Training and certification tracking

**Status:** Not implemented.  
**Effort:** 2 weeks  
**Owner:** Backend engineer + frontend engineer

**Deliverables:**

#### Backend
- [ ] Training catalog:
  - Add `Training` model: organization_id, name, description, category, required_for_roles, expiry_period_months
  - Support mandatory vs. optional

- [ ] Training records:
  - Add `EmployeeTraining` model: employee_id, training_id, completion_date, certificate_url, expires_at
  - Track compliance: completed, expiry coming soon, expired

#### Frontend
- [ ] My Training (employee):
  - List required and completed training
  - Upload certificate
  - View expiry dates

- [ ] HR → Training Management:
  - Training catalog management
  - Employee compliance report: who has completed, who is due, who is overdue
  - Send reminders for expiring certifications

**Acceptance criteria:**
- Training can be assigned to roles
- Employee completion and expiry are tracked
- Compliance reports work

---

### 5.6 Benefits and expense management

**Status:** Not implemented.  
**Effort:** 2–3 weeks  
**Owner:** Backend engineer + frontend engineer

**Deliverables:**

#### Backend
- [ ] Benefits enrollment:
  - Add `Benefit` model: organization_id, name (health insurance, 401k, gym membership), type, monthly_cost, coverage
  - Add `EmployeeBenefit` model: employee_id, benefit_id, enrolled_at, start_date, deduction_frequency (monthly, per-payroll)
  - On enrollment, start payroll deduction

- [x] Expense reimbursement:
  - Add `ExpenseRequest` model: employee_id, category, amount, description, receipt_url, status (draft, pending, approved, rejected, reimbursed)
  - Approval workflow: employee submits → manager approves → finance pays
  - Track payment date and reference

#### Frontend
- [ ] My Benefits (employee):
  - List available benefits
  - Enroll/unenroll
  - View deduction schedule

- [ ] My Expenses (employee):
  - Submit expense: category, amount, receipt upload
  - View status and reimbursement date
  - View past reimbursements

- [ ] HR → Benefits Management:
  - Benefit catalog
  - Employee enrollment report
  - Cost tracking

**Acceptance criteria:**
- [ ] Benefits can be enrolled and deducted from payroll
- [x] Expenses can be submitted, approved/rejected, and reimbursed with an audit trail
- [x] Reimbursement is tracked with date and payment reference

---

### 5.7 Integration marketplace and ecosystem

**Status:** Not applicable yet; foundation for future integrations.  
**Effort:** 2 weeks (planning + basic structure)  
**Owner:** Backend architect + DevOps

**Deliverables:**

#### Backend
- [ ] Integration framework:
  - Design integration interface: event → external service → response
  - Support popular services (Slack, Teams, Google Workspace, Microsoft 365, QuickBooks, Xero)
  - Webhook → Slack: new approval → "Approval needed for [entity]" in Slack channel
  - Webhook → Teams: same for Teams
  - Calendar sync: payroll dates, company holidays to Google/Microsoft calendars

- [ ] Configuration:
  - Settings → Integrations: show available integrations
  - Per integration: configuration form (API keys, channel, mapping)
  - Test connection button
  - Disable/remove integration

#### Frontend
- [ ] Integrations directory (future):
  - Browse available integrations
  - Install: configure and activate
  - Manage: connected integrations, settings, disconnect

**Acceptance criteria:**
- Integration framework is documented
- 2-3 sample integrations (Slack, Teams, Google Calendar) are working
- Integration settings are secure and tested

---

### Phase 5 Acceptance Criteria

- [ ] Saved reports can be generated and scheduled
- [ ] Role-based dashboards are functional
- [ ] API keys and webhooks are working
- [ ] SAML and SCIM are implemented
- [ ] Performance goals and reviews are functional
- [ ] Training and certification tracking works
- [ ] Benefits enrollment and deduction are working
- [ ] Expense reimbursement workflow is complete
- [ ] 2-3 integrations (Slack, Teams, Google Calendar) are functional

---

## Timeline and Sequencing

| Phase | Weeks | Start | End | Key Deliverables |
|-------|-------|-------|-----|-----------------|
| 1: Security & Tenancy | 8 | Week 1 | Week 8 | Isolation tests, MFA, auth hardening, audit logs, encryption, CSP |
| 2: SaaS Platform Ops | 10 | Week 9 | Week 18 | Onboarding, billing (Stripe), subscriptions, support tools |
| 3: Workforce Lifecycle | 16 | Week 19 | Week 34 | Org chart, onboarding/offboarding, approval workflows, documents, custom fields |
| 4: Payroll Compliance | 18 | Week 35 | Week 52 | Payroll locking, versioned rules, reconciliation, statutory reporting, payslips |
| 5: Advanced Features | ~12 | Week 53+ | ~Month 18 | Analytics, API/webhooks, SSO/SCIM, performance reviews, benefits, integrations |

**Total: ~18 months to full industry readiness (Phases 1–5)**

---

## Resource Estimates

### Team Composition (Recommended)

- **Backend Lead** (1 FTE): owner of data models, APIs, business logic, security
- **Frontend Lead** (1 FTE): owner of UI, state management, integrations, accessibility
- **QA Engineer** (0.5 FTE, scaling to 1 FTE in Phases 3–5): automated testing, regression, user acceptance testing
- **DevOps** (0.5 FTE): infrastructure, CI/CD, deployments, monitoring, backups
- **Product Manager** (1 FTE): requirements, prioritization, stakeholder feedback
- **Domain Expert/Compliance** (0.25–0.5 FTE, part-time): for payroll, tax, HR best practices

**Total: ~4–5 FTE**

### Effort Breakdown by Role

| Phase | Backend | Frontend | QA | DevOps | Product |
|-------|---------|----------|-----|---------|---------|
| 1 | 20 weeks | 8 weeks | 8 weeks | 4 weeks | 2 weeks (planning) |
| 2 | 16 weeks | 12 weeks | 6 weeks | 6 weeks | 4 weeks |
| 3 | 24 weeks | 20 weeks | 12 weeks | 2 weeks | 4 weeks |
| 4 | 28 weeks | 12 weeks | 12 weeks | 2 weeks | 4 weeks |
| 5 | 20 weeks | 16 weeks | 8 weeks | 2 weeks | 4 weeks |

---

## Critical Path and Dependencies

1. **Phase 1 must complete** before Phase 2 starts (security foundation)
2. **Phase 2 must complete** before going to production (business model)
3. **Phases 2 and 3 can overlap** (onboarding doesn't block workflows)
4. **Phase 4 depends on Phase 3** (approval workflows inform payroll controls)
5. **Phase 5 is additive** and can start in parallel with later Phase 4 work

---

## Risks and Mitigation

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Payroll compliance is complex; incorrect calculation harms customer trust | HIGH | Hire payroll expert early; validate all calculations against known benchmarks; external audit in Phase 4 |
| Multi-tenancy bugs hard to catch; leak data to wrong tenant | HIGH | Mandatory isolation tests for every feature; adversarial testing with 2+ organizations; regular audit |
| Stripe integration delays billing | MEDIUM | Use Stripe sandbox early; mock Stripe in tests; hire Stripe expert if needed |
| Scaling to large orgs (1000+ employees) may require DB optimization | MEDIUM | Performance test with 10k+ employee record; add indexes early; plan DB partitioning in Phase 2 |
| Scope creep; adding features during phases delays roadmap | MEDIUM | Strict backlog discipline; defer non-critical features to post-roadmap |
| Team turnover; critical knowledge loss | MEDIUM | Document all decisions; pair programming; knowledge base; cross-train team |

---

## Success Metrics

### Phase 1
- ✅ All isolation tests pass
- ✅ Zero security findings in external audit
- ✅ 100% endpoint authorization coverage
- ✅ Audit log captures all sensitive actions

### Phase 2
- ✅ 10+ organizations can be onboarded and isolated
- ✅ Trial → paid conversion works end-to-end
- ✅ Stripe webhooks are 100% reliable (monitored, alerted)
- ✅ Admin can manage organizations and support customers

### Phase 3
- ✅ Workflows (onboarding, approvals, documents) work for real HR scenarios
- ✅ Org chart and manager hierarchy functional
- ✅ Custom fields usable by customers
- ✅ Feature tests cover >80% of code paths

### Phase 4
- ✅ Payroll calculations pass validation against known benchmarks
- ✅ Statutory reports (BIR, SSS) are correct and submittable
- ✅ Payroll period locking prevents data corruption
- ✅ Reconciliation process works end-to-end

### Phase 5
- ✅ Reports can be scheduled and delivered
- ✅ API is documented; sample integrations work
- ✅ SSO/SCIM authentication works
- ✅ Advanced features (goals, training, benefits) are functional

---

## Go-to-Market Checklist (Post-Roadmap)

Before selling to first paying customer:
- [ ] Phase 1 complete (security audit passed)
- [ ] Phase 2 complete (billing working)
- [ ] Data backup and restore tested quarterly
- [ ] Disaster recovery plan documented and tested
- [ ] SLA and support procedures documented
- [ ] Privacy policy and terms of service reviewed by legal
- [ ] GDPR/data privacy compliance assessed
- [ ] Vulnerability disclosure policy published
- [ ] Security training for team completed
- [ ] Penetration testing completed with findings addressed
- [ ] Load testing: system handles 50+ concurrent users
- [ ] Monitoring and alerting in place
- [ ] Incident response plan documented
- [ ] Customer onboarding and success plan ready

---

## Long-term Vision (Post-Phase 5)

- **Mobile apps** (iOS/Android): clock-in, leave requests, approvals on mobile
- **AI/ML features**: attendance anomaly detection, attrition prediction, recommended leave allocation
- **Embedded analytics**: Tableau/Power BI dashboards
- **White-label SaaS**: reseller program for HR consultants
- **Regional expansion**: add payroll for Singapore, Indonesia, Malaysia, Vietnam
- **Recruitment**: ATS integrated with applicant tracking
- **Learning management**: course library, progress tracking
- **Employee experience platform**: wellness, engagement surveys, internal communication

---

## Conclusion

This roadmap prioritizes security, compliance, and operational maturity over feature breadth. By following this sequence, you move from "strong MVP" to "credible enterprise HRIS" in 12–18 months, with each phase adding strategic value:

1. **Security** (Phase 1) = Peace of mind
2. **Business model** (Phase 2) = Sustainable revenue
3. **HR competence** (Phase 3) = Competitive differentiation
4. **Financial trust** (Phase 4) = Enterprise credibility
5. **Ecosystem** (Phase 5) = Platform advantage

Good luck! 🚀
