# HRIS module gap audit

This audit records the most useful next capabilities after the current SaaS
and tenant-isolation foundation. Items are ordered roughly by product risk and
customer value; they are recommendations, not commitments.

## Cross-cutting SaaS platform

- **Tenant onboarding:** add a platform-owner console, organization signup,
  verified custom/subdomains, invitations, suspension/reactivation, and safe
  tenant deletion/export workflows. Artisan provisioning is currently the
  trusted administrative path.
- **Billing and plan lifecycle:** checkout, trial periods, subscription status,
  usage limits, invoices, failed-payment handling, and webhook-driven plan
  changes. Plan entitlements exist, but billing does not.
- **Authentication:** password reset, email verification, MFA, session/device
  management, SSO/SAML and SCIM for Enterprise.
- **Authorization:** add automated coverage for every route and enforce route
  metadata in the frontend guard as well as in navigation visibility.
- **Internationalization:** extract UI and email strings, tenant locale,
  currency/date/number formats, and country-specific payroll adapters. The
  Philippines remains the only payroll engine for now.
- **Notifications:** one tenant-aware notification center for in-app, email,
  and optional SMS/push delivery, with preferences and templates.
- **Reporting:** saved reports, exports, scheduled delivery, dashboards by
  role, and a stable analytics/audit data model.

## Core HR, users, and roles

- Add invitation-first user creation, activation/deactivation, bulk actions,
  employee lifecycle history, manager/reporting relationships, and an org chart.
- Add onboarding/offboarding checklists, asset handover, probation reminders,
  emergency contacts, dependants, and configurable custom employee fields.
- Record role assignment history and support approval for sensitive permission
  changes.

## Attendance and workforce calendar

- Add shift templates, schedules/rosters, breaks, grace periods, night shifts,
  attendance correction requests, manager approval, and geofence policies.
- Add biometric/device import connectors and anomaly detection for missed or
  duplicate punches.
- Integrate holiday providers through preview/import/review rather than direct
  payroll posting; support country, region, local/company days, and sync history.

## Leave and overtime

- Add leave carry-over, expiry, proration, accrual caps, negative-balance rules,
  probation eligibility, blackout dates, and configurable approval chains.
- Make working-day calculation explicitly account for schedules, holidays,
  weekends, half-days, and cross-year requests.
- Add team-calendar conflict warnings, delegation, substitute approvers, and
  employee balance forecasts.
- Add overtime rate policies by day type, pre-approval versus actual hours,
  rest/night differential rules, and explicit payroll posting/reconciliation.

## Payroll

- Add immutable payroll locking/versioning, adjustment runs, retroactive pay,
  bonuses, loans, reimbursements, final pay, employee payslips, and bank files.
- Version Philippine statutory tables by effective date and provide calculation
  test fixtures. Introduce a country adapter contract before adding another
  jurisdiction.
- Add maker-checker controls, variance reports, accounting exports, and
  payroll reconciliation.

## Announcements, messaging, and notes

- Announcements need audience targeting, scheduled publishing, attachments,
  acknowledgement tracking, expiry, and delivery analytics.
- Messaging needs full-text search, mentions, retention policies, moderation,
  delivery/read status, file controls, and notification preferences.
- Notes are currently deliberately private per user. Useful future additions
  are tags, reminders, checklists, optional sharing, and links to employees or
  meetings. Sharing must be explicit and independently authorized.

## Workplace Hub and employee documents

- Move room policies into tenant settings: booking lead time, duration, buffer,
  cancellation window, operating hours, equipment, and approval requirements.
- Complete recurrence handling, reminders, calendar invitations, conflict
  resolution, and Google/Microsoft calendar sync.
- Employee documents need version history, expiry reminders, configurable
  retention, access logs, approval, templates, and e-signature integrations.

## Automation, audit, and settings

- Replace arbitrary scheduled command entry with an allowlisted tenant-aware
  job catalogue. Add retries, run history, alerting, idempotency keys, and a
  worker health page.
- Make audit exports immutable/verifiable; add retention policies, actor/device
  filters, before/after diff presentation, and security-event alerts.
- Add typed validation and change history for every setting, plus encrypted
  secret storage for external provider credentials.

## Recommended delivery order

1. Tenant onboarding/domain management, password reset/MFA, and billing state.
2. Shift/working-day engine plus leave and overtime policy correctness.
3. Payroll locking, payslips, versioned Philippine statutory tables, and tests.
4. Notification center and approval-chain engine shared by HR workflows.
5. Reporting/exports, integrations, and the remaining Enterprise controls.
