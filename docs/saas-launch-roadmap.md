# Suitify HR: step-by-step SaaS launch roadmap

**Modern Aesthetic Input Overhaul & Enterprise UI Button Standardization (2026-09-11):** Elevated form field architecture across all tenant modules and platform consoles with solid surface backgrounds, subtle card-level resting depth, Linear/Stripe-style 3px focus rings, tabular numbers, and cross-theme native date/time indicator styling. Standardized UI buttons across all 25 frontend views strictly to canonical design standards (`variant="flat"` for primary CTAs, `variant="tonal"` for secondary/toolbar actions, `class="text-none"` universal text casing). Full test suite: 187 tests passed (1,683 assertions); tenancy audit passed for 69 tables; authorization audit passed; encryption audit passed; frontend build passed (650 modules, 11.90s); 15/15 frontend tests passed.

**Universal Sharp UI Unification, Interactive Org Chart Canvas & Leave Forecasting Engine (2026-09-11):** Enforced universal zero-border-radius design standard across all Vuetify components, custom cards, dialogs, sheets, and marketing/platform views. Implemented an interactive hierarchy visualization canvas (`OrgChartTree.vue` & `OrgChartNode.vue`) in Core HR integrated with `GET /api/v1/organization-chart`. Implemented multi-month leave balance forecasting engine (`GET /backend/api/v1/leave-credits/forecast`) projecting accruals against tenant settings and employee tenure. Full test suite: 187 tests passed (1,683 assertions); tenancy audit passed for 69 tables; authorization audit passed; frontend build passed (650 modules, 11.28s); 15/15 frontend tests passed.

**Batch 1 Module Improvements & Autonomous Session Protocol (2026-09-11):** Delivered root `AGENTS.md` and `docs/session-logs.md` establishing mandatory autonomous startup reading without user prompts. Implemented 15-minute password reset token hardening, platform owner invitation resend/revocation management, recursive organizational chart hierarchy endpoint (`GET /api/v1/organization-chart`), Philippine Night Shift Differential (NSD) auto-calculation in attendance work summaries, and pre-flight payroll variance analysis (`GET /api/v1/payroll-periods/{id}/variance`). Full test suite: 183 tests passed (1,651 assertions); tenancy audit passed for 69 tables; authorization audit passed; frontend build passed (649 modules, 11.06s).

**Legacy-label cleanup (2026-09-11):** Removed appended former-brand labels from login and tenant navigation so the shared Suitify HR wordmark renders only once. The production build passed (649 modules), all 6 branding/routing tests passed, and the local frontend container was rebuilt.

Updated: 2026-09-10

**Wordmark-only identity (2026-09-11):** Active web product surfaces now use a text-rendered Suitify HR wordmark matching the approved white-and-blue reference, without a separate symbol. The browser identity uses a matching SVG wordmark; obsolete web PNG and ICO logo assets were removed. The production build passed (649 modules) and all 6 branding/routing tests passed. Mobile-store icon replacement remains pending until a mobile release is planned.

**Windows local Docker verification (2026-09-11):** The complete local Compose stack builds after a transient Docker DNS failure cleared. The frontend image health check now targets `127.0.0.1` because Alpine resolved `localhost` to IPv6 while Nginx listened on IPv4. Backend, frontend, MySQL, Redis, and Mailpit report healthy; queue, scheduler, and Reverb are running. Frontend, API health, and Mailpit HTTP checks passed.

**Suitify HR brand update (2026-09-11):** Product-facing names and active technical identifiers now use Suitify HR across frontend entry points, application defaults, Docker runtime, deployment fixtures, backups, signup, authentication, billing, and Platform Console. The logo component and source assets were renamed; no former-brand references remain. The frontend production build passed (651 modules), branding/route tests passed (6 tests), and staging-validator tests passed (3 tests, 11 assertions). Windows absolute paths are now accepted by the staging validator. Staging visual/legal review remains pending.

This is the execution plan from our current build to a verified release. Use the [industry roadmap](industry-readiness-roadmap.md) for feature history and this document for the order of work, expected behavior, and release evidence.

The previous product rebrand from 2026-09-10 was superseded by Suitify HR on 2026-09-11. The existing application mark and icon matrix remain pending visual review and replacement if they do not fit the new brand. External email, backup/restore behavior, domain registration, and formal trademark clearance remain pending.

Local staging-readiness tooling completed on 2026-09-10. Production Compose now keeps the Platform Console provisioning key in the HTTP app only, binds configurable frontend/Reverb ports to loopback by default, and waits on explicit service health checks. The secret-safe staging validator, disposable one-command production-topology smoke test, and infrastructure/topology runbook are implemented. Verification passed for 3 validator tests / 11 assertions, both deployment test files (including all 4 billing scenarios), Compose rendering, shell syntax, the frontend production build, and a complete disposable stack run covering health probes, migration status, queue/scheduler inspection, and the 69-table tenancy audit. This does not mean staging exists: DNS/TLS, SMTP, Stripe test mode, durable backups, monitoring, and rollback still require external resources and real-environment evidence.

Production baseline hardening completed locally on 2026-09-10. Production Compose now keeps Stripe configuration out of Reverb and frontend services while supplying it to the app, migration, queue, and scheduler services. Shift roster time values are normalized to `HH:mm` on both SQLite and MySQL, and affected regression tests are database-independent. Verification passed for all 4 Compose billing scenarios, production Compose rendering, 13 focused MySQL tests / 62 assertions, the full MySQL backend suite / 170 tests / 1,554 assertions, the 69-table tenancy audit, and the frontend production build. This is implementation and local verification only; the candidate remains uncommitted and staging/provider verification is still pending.

## Where we are

| Area                           | Current status                             | Evidence / remaining work                                                                      |
| ------------------------------ | ------------------------------------------ | ---------------------------------------------------------------------------------------------- |
| Free Basic                     | Implemented                                | Employee capacity is enforced; signup reads the public allowance.                              |
| Philippine payroll restriction | Implemented                                | Payroll entitlements and related APIs require PH. Other countries retain eligible HR features. |
| Platform pricing editor        | Implemented                                | `/platform-console/pricing`: allowance, peso rate, effective date, preview, history.           |
| Pricing versions               | Implemented                                | Global platform settings hold versions; operation logs record changes.                         |
| Checkout calculation           | Implemented; provider verification pending | Saved rate and employee quantity are sent to Stripe.                                           |
| Subscription synchronization   | Implemented; provider verification pending | Uses the subscribed allowance, keeps the price, detects provider failures.                     |
| Webhook handling               | Partially verified                         | Paid/unpaid handling and duplicate event IDs tested; wider lifecycle scenarios remain below.   |
| Deployment                     | Local topology verified; staging pending   | Validator, health gates, smoke test, and runbook pass locally; external services remain.       |
| Public paid launch             | Pending                                    | Complete the release gates below.                                                              |

Latest release verification: 4 Compose billing scenarios passed; the MySQL-backed backend suite passed with 170 tests and 1,554 assertions; the tenancy audit passed for 69 tables; and the frontend production build passed. The host lacks `pdo_sqlite`, so the ordinary SQLite-backed suite was not rerun. This does not establish a successful live Stripe checkout or a production deployment.

## How the current flow works

1. Platform staff save a pricing version in **Platform Console → Pricing**.
2. Its effective date determines when the public pricing endpoint starts returning it.
3. The marketing calculator and signup allowance read that endpoint.
4. Checkout counts the organization's employees whose employment has not ended, subtracts the free allowance, and sends quantity and unit price to Stripe. Future hires reserve places; an employee counts through their last employment day.
5. Checkout stores the pricing version and free allowance in Stripe metadata. A paid checkout webhook activates the subscription.
6. Daily reconciliation reads the subscribed allowance from Stripe metadata and updates the quantity while retaining the existing price. Identical quantities need no update.
7. Failed synchronization is reported and retried during the next scheduled run. Webhook receipts and subscription changes commit together, so a repeated event ID does not repeat the change.

Rates are stored in minor currency units: `1900` means PHP19.00. The console accepts pesos and converts them before saving.

For a 10-person allowance and PHP19 rate, 25 active employees produce 15 billable employees, or PHP285/month before any provider invoice adjustments.

### Behavior that must be settled before launch

-   Resolved: Growth has no minimum by default. Platform Console → Pricing → Minimum billable employees controls this: 0 means no minimum; 1 or more sets a floor multiplied by the employee rate. New subscriptions snapshot the setting; existing snapshots are preserved. Missing historical minimum metadata defaults to zero.
-   Annual Growth currently uses ten times the monthly unit rate. Confirm this commercial rule and disclose it before selling annual subscriptions.
-   Editing the public free allowance affects Basic capacity globally. Existing Stripe subscriptions use their stored allowance. Decide whether existing Basic organizations also need a grandfathered allowance.
-   Legacy Stripe subscriptions without allowance metadata are skipped by quantity synchronization. Do not infer or rewrite their commercial terms automatically.
-   Quantity updates currently request proration. Verify actual invoice credits and charges in Stripe test mode.
-   Checkout currently estimates the period end from the processing date. Verify and replace this with authoritative provider period data before relying on billing dates.

## Step 1 — Establish a reproducible baseline

Owner: development. Status: local baseline verified on MySQL 2026-09-10; staging baseline pending. Frontend production build also passed.

Baseline: current backend work is based on `0095e69ce61d5c5a726eb4861a92510f19848fd9`; frontend `fa8d7d63e7668de8bf7930bb8b32a525d49969fc`. The production-hardening changes are not yet committed, so record the final backend commit after review. PHP 8.3.33 / isolated MySQL 8.4: full suite passed, 170 tests and 1,554 assertions. `php artisan tenancy:audit` passed for 69 tables. All 4 Compose billing scenarios and the frontend production build passed. The host lacks `pdo_sqlite`, so SQLite was not rerun. These are local checks, not staging acceptance.

1. Record the backend and frontend commit IDs for the candidate release.
2. Run the backend test suite from `hris-backend`:

    ```powershell
    php artisan test
    php artisan tenancy:audit
    ```

3. Run `npm.cmd run build` from `hris-frontend`.
4. Record failures, commands, and environment details. Fix relevant defects and rerun the affected tests before broadening verification.

Done when: the release candidate has recorded test/build results and each unresolved issue has an explicit owner. Never treat an old test count as evidence for a new commit.

## Step 2 — Align the commercial rules and screens

Owner: product owner + development. Status: in progress.

Delivered 2026-09-10: shared peso formatting preserves centavos; marketing shows loading/unavailable states instead of fallback prices. The owner approved no minimum for Growth. A versioned minimum-billable-employees console setting now feeds checkout metadata, subscription synchronization and previews. Zero-cost checkout uses a zero-price recurring line; reconciliation restores the snapshotted employee rate with the correct quantity, including zero. Verify this zero-cost-to-paid transition in Stripe test mode before launch. Annual pricing and Basic grandfathering remain open. Verification: 10 focused backend tests / 40 assertions and 5 pricing utility tests passed.

1. Resolve the minimum Growth charge, annual multiplier, and Basic grandfathering behavior described above.
2. Ensure marketing, signup, tenant billing, platform previews, and checkout describe the same rules.
3. Verify a price such as PHP19.50 displays correctly; do not hide centavos with whole-peso formatting.
4. Verify price-loading failures show an unavailable state rather than a stale advertised default.
5. Confirm the current Growth employee cap fits the per-employee offer and is communicated consistently.

Done when: every offered price can be reproduced by the calculator and checkout, including headcounts below, at, and above the allowance.

## Step 3 — Prepare isolated staging

Frontend production build passed after the configurable minimum implementation. No schema migration was needed.

Owner: operations + development. Status: local preparation complete; external staging pending.

Delivered 2026-09-10: Compose forwards Stripe API/webhook secrets and billing portal return hosts only to the PHP services that require them; the Platform Console provisioning key reaches only the HTTP app. Public ports are configurable and loopback-bound by default, all long-running services have health checks, and startup dependencies use health/completion gates. The environment template, secret-safe validator, infrastructure runbook, and disposable `deploy/smoke-local.sh` rehearsal are ready. Local evidence includes configuration regression tests and a successful full-stack run with migrations, health probes, queue/scheduler checks, a 69-table tenancy audit, and automatic cleanup. Next: supply isolated external staging infrastructure and credentials, then repeat runtime, email, Stripe, backup, and rollback checks there.

1. Use separate staging database, storage, email delivery and Stripe test credentials.
2. Review [deployment instructions](deployment.md) and [deployment readiness](deployment-readiness-checklist.md).
3. Supply the runtime variables required by billing, including `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET` and `BILLING_PORTAL_RETURN_HOSTS`. Verify they reach the running application; placing them in a host file alone is insufficient.
4. Compose billing environment mapping is implemented and locally tested. Verify the settings reach running staging containers before testing checkout; configuration rendering alone does not verify runtime credentials.
5. Rehearse migrations against staging MySQL after a backup. The existing Compose migration service runs `php artisan migrate --force`; do not run a fresh/reset migration against customer data.
6. Verify HTTPS, authenticated API access, private employee files, email, queue worker and scheduler.

Done when: a clean staging deployment works without manual database edits or production credentials.

## Step 4 — Verify pricing changes end to end

Owner: development / QA. Status: pending browser verification.

1. Sign in to `/platform/login`, then open `/platform-console/pricing`.
2. Save a version effective now; verify the history and operation log.
3. Check the public page, signup allowance and new checkout against that version.
4. Schedule a future version; verify it remains inactive until its effective instant.
5. Verify the new version activates and existing subscribed prices and allowances stay intact.
6. Check that tenant users and requests without the platform key cannot edit pricing.

Done when: screenshots and API results demonstrate both immediate and scheduled pricing behavior, including authorization failures.

## Step 5 — Verify Stripe checkout and webhooks

Owner: development / QA. Status: next provider integration milestone.

The application API prefix is `/backend/api/v1`. The webhook is `/backend/api/v1/billing/stripe/webhook`. Confirm reverse-proxy routing before registering it in Stripe test mode.

1. Complete a real test-mode checkout through the tenant billing screen.
2. Compare Stripe's quantity, unit amount, currency, metadata and invoice total with the preview.
3. Confirm unpaid checkout completion does not activate access; confirm delayed payment success does.
4. Redeliver the same event ID and verify there is only one subscription transition.
5. Verify invalid signatures fail and a failed database transaction can be retried successfully.
6. Test failed payment, recovery, cancellation, and events delivered out of order. Add missing handlers or ordering protections discovered by these cases.
7. Verify actual billing-period dates rather than assuming they start when a webhook is received.

Done when: each scenario has a test event ID, expected state, observed state and pass/fail result. Do not record API keys or raw customer payment information.

## Step 6 — Verify automated headcount billing

Owner: development / QA. Status: pending provider verification.

1. Add, end and reactivate employees in a staging organization.
2. Run a targeted reconciliation in staging:

    ```powershell
    php artisan subscriptions:reconcile --organization=YOUR-STAGING-SLUG
    ```

3. Compare the quantity against that subscription's allowance and verify invoice prorations.
4. Repeat without changing headcount; verify no unnecessary update is sent.
5. Simulate a Stripe failure; verify the command reports failure, continues with other organizations, and succeeds on a later run.
6. Verify employees in another organization never affect the quantity.
7. Check a legacy subscription without metadata is left untouched and has an explicit review path.

The scheduler currently runs reconciliation at 01:00 in the application scheduler timezone. Docker uses `schedule:work`; a non-Docker deployment needs `schedule:run` every minute. Verify the configured timezone and avoid duplicate scheduler instances.

Done when: headcount changes reach Stripe automatically and failed synchronization is visible to operations.

## Step 7 — Verify customer workflows and payroll scope

Owner: QA / product owner. Status: pending staging acceptance.

1. Create a Free Basic workspace, sign in and complete password recovery.
2. Add employees up to the currently published allowance; verify the next active employee is blocked.
3. Upgrade and verify the intended capacity and features become available.
4. Exercise attendance, leave submission, approval, notifications and employee profile access using separate user roles.
5. Verify PH payroll access on an eligible plan. Verify non-PH organizations cannot use payroll APIs, statutory reports or related payroll screens.
6. Verify a second organization cannot read the first organization's records or private files.

Use the [browser checklist](staging-verification-checklist.md) as supporting scenarios. Its older fixed-10 examples and assumed UI routes must be reconciled with this release before use; they are not proof of implemented behavior.

Done when: complete browser journeys pass for an owner, manager and employee, with actual evidence rather than a feature checklist alone.

## Step 8 — Rehearse operations

Owner: operations. Status: pending.

1. Restore a staging backup into a separate environment and verify database records and private files.
2. Confirm failed jobs, failed reconciliation and unavailable services are visible in logs/monitoring.
3. Verify queue and scheduler restart behavior after a deployment.
4. Rehearse application rollback with a known previous image; review database compatibility before rollback.
5. Assign an owner for payment failures and customer exceptions that automation cannot resolve.

Done when: recovery and incident steps have been executed, timed and recorded. Automation reduces routine work; exceptions still need an accountable operator.

## Step 9 — Limited release, then public launch

Owner: product owner + operations. Status: pending.

1. Close the preceding release blockers and record approval for the exact release candidate.
2. Deploy to a limited group using the verified pricing policy.
3. Observe signup, email, first payment, subscription updates and support exceptions.
4. Resolve defects before expanding availability.
5. Mark public paid launch complete only after provider, workflow and operational evidence is recorded.

## How we maintain this roadmap

For each implementation: record the behavior changed, format affected files, add meaningful workflow tests, run relevant checks, and update this document plus the industry roadmap. Generate new migrations with `php artisan make:migration`; audit tenancy after schema changes.

Use this evidence template for every milestone:

```text
Step:
Backend/frontend commit IDs:
Environment and date:
Commands or browser journey:
Expected result:
Observed result:
Evidence location (no secrets):
Open issues and owner:
Status: pending / in progress / verified
```

Implementation complete and staging verified are separate statuses. Do not mark a milestone verified merely because code exists.

**Native input icon theme correction (2026-09-11):** Removed the extra dark-mode inversion from App.vue so native date/time icons use the browser's existing dark color scheme; extended picker styling to month/week fields. Production and Docker builds passed (655 modules); authorization and encryption audits passed. Local frontend container rebuilt; HTTP and served CSS verified at 127.0.0.1:3000. Browser visual verification remains pending; next step is to refresh and check picker contrast in both themes. No launch gate status changed.

**UI standardization review (2026-09-11):** Re-audited the seven-phase UI checklist; centralized theme-aware input/button styles, preserved validation/floating-label/textarea behavior, aligned native date/time icons, removed conflicting table sizing, improved mobile action wrapping and touch targets, and filled missing button variants/accessibility names. See [UI standardization checklist](ui-standardization-checklist.md). Verification: 187 backend tests (1,683 assertions), 15 frontend tests, tenancy (69 tables), authorization and encryption audits all passed; production and final Docker builds passed (656 modules). Chrome checks passed for shared controls in both themes, keyboard/clear/select/textarea interactions, 390px mobile layout and 44px touch targets. Local frontend rebuilt and served CSS verified. Remaining: Safari/Firefox and complete authenticated workflow acceptance; next step is reviewing real module data in the refreshed UI. No readiness gate was advanced.

## AWS staging infrastructure progress (2026-09-12)

User-guided deployment now runs on Lightsail suitify-staging in Singapore (2 vCPU, 4 GB RAM, static IPv4 52.76.75.229). The user merged develop into main for deployment and pulled the repositories under /home/ubuntu/suitify. Docker hello-world and Compose v5.5.1 were verified through terminal output. Image builds passed per user report after retrying an npm ECONNRESET. Configuration is stored outside the repositories in /home/ubuntu/suitify/staging.env. Local frontend /healthz returned HTTP 200.

Caddy logs confirm certificate issuance for suitify.52-76-75-229.sslip.io. After adding the missing inbound HTTPS TCP 443 rule, the user reports the site is accessible. This is infrastructure progress, not full staging acceptance: remote service/migration status, tenancy/authorization/encryption audits, account provisioning, authenticated workflows, tenant hostname certificates, backups/restore, monitoring, and rollback still need verification. Email currently uses the log driver; Stripe is disabled. Provider verification gates remain open. Next: collect Compose service status and backend audit results, then provision staging access.

**AWS staging runtime checks (2026-09-12):** User terminal output confirms all eight long-running application services healthy and migration service exited 0. Remote tenancy audit passed for 69 tables; strict authorization audit passed; encryption audit passed for all six configured sensitive fields. These are runtime configuration/schema checks, not full backend test or workflow coverage. Next: initialize roles/permission catalogue and organization defaults, create a unique administrator in the existing default staging organization, and verify authenticated workflows. Public signup creates separate organizations, while the current base hostname resolves to the default legacy organization; account setup must respect that routing. Email delivery, Stripe, backups/restore, monitoring, and rollback remain unverified.

## Organization setup hardening (2026-09-13)

Implemented locally: `platform:setup` initializes the existing default workspace, roles, permissions and HR defaults and privately prompts for its first administrator password. Retries preserve accounts/passwords, customized default values and nonempty User-role assignments. `organizations:create` now uses shared provisioning with country/timezone inputs, validation and subscription history. Baseline DatabaseSeeder no longer creates demo credentials; direct demo AdminSeeder is blocked outside local/testing. Production Docker excludes nested environment secrets.

Platform owner validation rejects a false invitation flag without a password and normalizes boolean input. Console creation now supports owner invitations, suggests the workspace slug and returns a private invitation link plus transport status. Log/array mail is no longer reported as delivered. Signup and invitation completion link to the tenant-specific login; production frontend API calls use the current hostname via /backend/api/v1.

Verification: full backend suite passed (206 tests, 1,823 assertions); tenancy audit passed for 69 tables; strict authorization and encryption audits passed; all 5 Compose regression checks and all 15 frontend tests passed. Final frontend production build passed (658 modules, Vite 18.69s). No schema change. These changes are not deployed to AWS. Next: merge/push both repositories to main, pull and rebuild the staging images, run platform:setup, and verify owner login. Additional tenant hostnames still need DNS/TLS; SMTP/provider, backup/restore, monitoring, rollback and browser acceptance gates remain open. See [Organization setup](organization-setup.md) and [review findings](organization-provisioning-review.md).
