# Suitify HR: Master Module Improvement Roadmap

This document provides the canonical improvement roadmap and architectural specifications for all modules in Suitify HR. Every agent working on feature additions, refactoring, or compliance hardening must consult and update this document.

---

## Progress Overview

| Module Area                                | Status              | Key Focus                                                                                      |
| :----------------------------------------- | :------------------ | :--------------------------------------------------------------------------------------------- |
| **1. Authentication & Onboarding**         | Advanced Foundation | MFA, 15m password reset, invitation management, enterprise domains                             |
| **2. Core HR & 201 Files**                 | In Progress         | Organizational chart hierarchy, effective-dated history, custom fields                         |
| **3. Attendance & Rostering**              | In Progress         | Night Shift Differential (NSD), geofencing, biometric punch sync                               |
| **4. Leave & Overtime**                    | In Progress         | Leave forecasting, overtime pre-approval reconciliation, holiday awareness                     |
| **5. Philippine Payroll Compliance**       | Advanced Foundation | TRAIN tax & statutory tables active; adding variance check, PDF payslips, BIR/SSS transmittals |
| **6. Approvals & Workflows**               | In Progress         | Multi-tier sequential approval engine, SLA escalations, email actions                          |
| **7. Benefits & Expenses**                 | Implemented MVP     | Receipt preview, de minimis caps, auto-feed approved expenses to payroll                       |
| **8. Workplace Hub**                       | Implemented MVP     | Room booking & recurrence active; adding iCal/Google calendar sync                             |
| **9. Internal Communications**             | Implemented MVP     | Announcements & messaging active; adding audience targeting & WebSockets                       |
| **10. SaaS Operations & Platform Console** | Advanced Foundation | Pricing editor & health active; adding automated dunning & audit signatures                    |

---

## 1. Authentication, Onboarding & Multi-Tenancy

### Completed Foundation

-   [x] Multi-tenant isolation enforced via `BelongsToOrganization` across 69 tables.
-   [x] Host-scoped login rejection and tenant slug resolution.
-   [x] Multi-Factor Authentication (TOTP) and encrypted recovery codes.
-   [x] Brute-force throttling and generic authentication error messages.
-   [x] Session listing and remote device revocation.
-   [x] Free Basic 10-seat limit hard-enforced at organization mutation boundaries.

### In Progress & Planned Improvements

-   [x] **[Batch 1] Password Reset Token Expiry Hardening:** Reduced token expiration from 60 minutes to 15 minutes (`config/auth_security.php` & `config/auth.php`).
-   [x] **[Batch 1] Owner Invitation Resend & Revoke:** Platform operator endpoints to resend expired invitations and revoke compromised/pending invitations (`POST .../resend`, `DELETE .../revoke`).
-   [ ] **Custom Enterprise Subdomains & CNAME:** Automated DNS validation and TLS certificate provisioning for `organization.suitifyhr.com`.
-   [ ] **SAML 2.0 Identity Provider Adapter:** Enterprise SSO support for Okta and Microsoft Azure Active Directory alongside existing OIDC.

---

## 2. Core HR & Employee 201 Records

### Completed Foundation

-   [x] Employee profile CRUD with salary, pay schedule, and emergency fields.
-   [x] Tenant-scoped Departments, Positions, Job Grades, and Employment Statuses.
-   [x] Private profile photo storage and encrypted employee document attachments.
-   [x] Automated employee number generation formats (`EMP-YYYY-XXXXXX`).

### In Progress & Planned Improvements

-   [x] **[Batch 1] Organizational Chart Hierarchy API (`GET /api/v1/organization-chart`):** Recursive parent-child hierarchy built from `manager_id` on employee records with cycle detection.
-   [ ] **Interactive Org Chart UI:** Visual tree representation in `EmployeeManagement` view with zoom/pan and manager re-assignment modal.
-   [x] **Interactive Org Chart UI:** Visual tree representation in `EmployeeManagement` view with zoom/pan, search filter, direct report count badges, and employee profile inspection.
-   [ ] **Effective-Dated Employment History (`employment_histories`):** Record promotion, salary adjustment, and department transfer audit logs with effective dates.
-   [ ] **Custom Employee Fields (EAV/JSON metadata):** Configurable tenant fields (e.g. Uniform Size, PRC License, SSS/TIN format validators).

---

## 3. Attendance & Shift Rostering

### Completed Foundation

-   [x] Clock-in and Clock-out capture with photo, location coordinates, and IP address.
-   [x] Shift template definition and employee shift assignment with normalized `HH:mm` time casting.
-   [x] Attendance correction request submission and manager approval flow.
-   [x] Tardy (late) and undertime minute calculation.

### In Progress & Planned Improvements

-   [x] **[Batch 1] Philippine Night Shift Differential (NSD):** Auto-calculation of minutes worked between 10:00 PM and 6:00 AM (Article 86 of the Philippine Labor Code) in attendance work summaries.
-   [ ] **Geofencing & IP Allowlisting:** GPS bounding circle (100m radius) and office WiFi BSSID/IP enforcement prior to punch recording.
-   [ ] **Biometric Punch Ingestion:** API endpoint (`POST /api/v1/attendance/punch-import`) accepting CSV and ZKTeco biometric timeclock feeds.
-   [ ] **Grace Period Rules:** Configurable company setting for tardiness grace windows (e.g., 15 minutes before penalties accrue).

---

## 4. Leave & Overtime Management

### Completed Foundation

-   [x] Configurable Leave Types (Vacation, Sick, Emergency, etc.) with paid/unpaid flags.
-   [x] Automated recurring leave credit accrual via scheduled command (`leave:accrue-credits`).
-   [x] Leave conversion request submission and cash conversion calculation.
-   [x] Overtime policy calculation by day type (Regular Day 1.25×, Rest Day 1.30×, Holiday 2.0×).

### In Progress & Planned Improvements

-   [ ] **Leave Balance Forecasting (`GET /api/v1/leave-credits/forecast`):** Calculates projected accrued balances at future dates for advance holiday planning.
-   [x] **Leave Balance Forecasting (`GET /api/v1/leave-credits/forecast`):** Calculates projected accrued balances at future dates for advance holiday planning based on tenant accrual settings and tenure.
-   [ ] **Overtime Pre-approval vs. Actual Punch Reconciliation:** Flags overtime claims where requested hours exceed actual biometric clock-out times.
-   [ ] **Holiday & Roster Exclusion:** Automatically excludes declared workforce holidays and rostered rest days from leave duration math.

---

## 5. Philippine Payroll Compliance

### Completed Foundation

-   [x] Semi-monthly and monthly payroll calculation with TRAIN law graduated withholding tax.
-   [x] SSS contribution calculation (Employee, Employer, and EC contributions based on MSC).
-   [x] PhilHealth premium calculation (5% shared equally, capped at ceiling).
-   [x] Pag-IBIG mandatory contributions.
-   [x] Payroll period locking and immutable item calculation snapshots.
-   [x] Maker-checker separation: creator cannot approve or pay their own payroll run.

### In Progress & Planned Improvements

-   [x] **[Batch 1] Pre-Flight Payroll Variance Analysis (`GET /api/v1/payroll-periods/{id}/variance`):** Compares draft period against prior locked period, flagging anomalies (>15% variance in gross, net, or deductions).
-   [ ] **Encrypted PDF Payslips:** Automated PDF generation with password protection (employee DOB/TIN), stored in private disk and distributed via email.
-   [ ] **Off-Cycle Retroactive Adjustment Runs (`payroll_adjustment_runs`):** Independent adjustment periods referencing locked baselines for retroactive pay or disputes.
-   [ ] **Government Electronic Transmittals:**
    -   **SSS:** Electronic R-3/R-5 file generator.
    -   **PhilHealth:** EPRS remittance file export.
    -   **Pag-IBIG:** Monthly Remittance Schedule (MCRF) CSV format.
    -   **BIR:** Form 1601-C and annual Form 2316 tax certificates.

---

## 6. Approvals & Workflows

### Completed Foundation

-   [x] Centralized Approval Inbox for leave, overtime, and attendance corrections.
-   [x] Temporary approval delegation with expiry dates.
-   [x] `ApprovalWorkflow` and `ApprovalWorkflowStep` models with condition criteria.

### In Progress & Planned Improvements

-   [ ] **Multi-Tier Sequential Approval Engine:** Execute multi-step approval pipelines (Supervisor → Dept Head → HR) rather than stopping at the first matching step.
-   [ ] **SLA Auto-Escalation:** Re-route pending approvals to designated backup approvers after 48 hours of inactivity.
-   [ ] **One-Click Email Approvals:** HMAC-signed one-time action links in notification emails.

---

## 7. Benefits & Expense Claims

### Completed Foundation

-   [x] Tenant-scoped Benefit Plans and employee enrollments with recurring payroll deductions.
-   [x] Expense claim submission, manager review, and finance reimbursement tracking.
-   [x] **Private Receipt Upload & Preview UI:** Private tenant disk storage (`organizations/{id}/expense-receipts`), secure authorized streaming endpoint (`GET /api/v1/expense-claims/{id}/receipt`), and inline image/PDF preview modal.
-   [x] **Philippine Statutory De Minimis Ceilings (`GET /api/v1/benefit-plans/de-minimis-ceilings`):** BIR RR 2-98 and RR 11-2018 tax-exempt thresholds (Rice Subsidy ₱2,000/mo, Clothing ₱6,000/yr, Medical cash ₱250/mo, Medical assistance ₱10,000/yr, Laundry ₱300/mo, Awards ₱10,000/yr, Gifts ₱5,000/yr, Overtime meals 25% min wage) with dynamic advisory banners and excess taxable alerts.
-   [x] **Benefit Plan Management & Roster Listing:** HR plan editing, active/inactive toggling, enrolled roster modal (`GET /api/v1/benefit-plans/{id}/enrollments`), and unenrollment cancellation (`DELETE /api/v1/benefit-enrollments/{id}`).
-   [x] **Accounting CSV Export (`GET /api/v1/expense-claims/export`):** Tenant expense claims CSV download filtered by status.

### In Progress & Planned Improvements

-   [ ] **Direct Payroll Reimbursement Flow:** Automatically append reimbursed expense claims as non-taxable allowances in the next open payroll run.

---

## 8. Workplace Hub & Meetings

### Completed Foundation

-   [x] Meeting room configuration with capacity limits and double-booking prevention.
-   [x] Daily, weekly, and monthly recurrence scheduling.
-   [x] Agenda, minutes, and action-item tracking.

### In Progress & Planned Improvements

-   [ ] **Calendar Interoperability (ICS/iCal):** Generate `.ics` files and expose authenticated calendar feeds for Google Calendar and Microsoft 365.
-   [ ] **Room Amenities Matrix:** Track whiteboard, projector, and video conference equipment.

---

## 9. Internal Communications

### Completed Foundation

-   [x] Company announcements with HTML sanitization defense.
-   [x] Direct and group messaging with realtime configuration.
-   [x] Private personal notes with strict tenant and creator isolation.

### In Progress & Planned Improvements

-   [ ] **Audience Segmentation:** Target announcements by department, position, or role with mandatory read-acknowledgement.
-   [ ] **Realtime Push Notifications:** Broadcast instant notifications via Laravel Reverb on port 8080.

---

## 10. SaaS Operations & Platform Console

### Completed Foundation

-   [x] Platform Console with tenant management, plan switching, and health monitoring.
-   [x] Stripe checkout sessions, webhooks (`checkout.session.completed`, `invoice.payment_failed`), and quantity reconciliation.
-   [x] Versioned Philippine pricing editor and public calculator integration.

### In Progress & Planned Improvements

-   [ ] **Stripe Invoice & Receipt History in Customer UI:** Expose customer billing receipts and past invoices in `Billing.vue`.
-   [ ] **Automated Dunning Workflow:** Day 1, 3, and 7 grace-period email reminders prior to workspace suspension.
-   [ ] **Cryptographic Audit Manifest:** SHA-256 digital signature manifest for compliance CSV exports.
