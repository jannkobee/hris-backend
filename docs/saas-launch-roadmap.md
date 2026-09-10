# LexisOne: step-by-step SaaS launch roadmap

Updated: 2026-09-10

This is the execution plan from our current build to a verified release. Use the [industry roadmap](industry-readiness-roadmap.md) for feature history and this document for the order of work, expected behavior, and release evidence.

Local staging-readiness tooling completed on 2026-09-10. Production Compose now keeps the Platform Console provisioning key in the HTTP app only, binds configurable frontend/Reverb ports to loopback by default, and waits on explicit service health checks. The secret-safe staging validator, disposable one-command production-topology smoke test, and infrastructure/topology runbook are implemented. Verification passed for 3 validator tests / 11 assertions, both deployment test files (including all 4 billing scenarios), Compose rendering, shell syntax, the frontend production build, and a complete disposable stack run covering health probes, migration status, queue/scheduler inspection, and the 69-table tenancy audit. This does not mean staging exists: DNS/TLS, SMTP, Stripe test mode, durable backups, monitoring, and rollback still require external resources and real-environment evidence.

Production baseline hardening completed locally on 2026-09-10. Production Compose now keeps Stripe configuration out of Reverb and frontend services while supplying it to the app, migration, queue, and scheduler services. Shift roster time values are normalized to `HH:mm` on both SQLite and MySQL, and affected regression tests are database-independent. Verification passed for all 4 Compose billing scenarios, production Compose rendering, 13 focused MySQL tests / 62 assertions, the full MySQL backend suite / 170 tests / 1,554 assertions, the 69-table tenancy audit, and the frontend production build. This is implementation and local verification only; the candidate remains uncommitted and staging/provider verification is still pending.

## Where we are

| Area                           | Current status                                        | Evidence / remaining work                                                                      |
| ------------------------------ | ----------------------------------------------------- | ---------------------------------------------------------------------------------------------- |
| Free Basic                     | Implemented                                           | Employee capacity is enforced; signup reads the public allowance.                              |
| Philippine payroll restriction | Implemented                                           | Payroll entitlements and related APIs require PH. Other countries retain eligible HR features. |
| Platform pricing editor        | Implemented                                           | `/platform-console/pricing`: allowance, peso rate, effective date, preview, history.           |
| Pricing versions               | Implemented                                           | Global platform settings hold versions; operation logs record changes.                         |
| Checkout calculation           | Implemented; provider verification pending            | Saved rate and employee quantity are sent to Stripe.                                           |
| Subscription synchronization   | Implemented; provider verification pending            | Uses the subscribed allowance, keeps the price, detects provider failures.                     |
| Webhook handling               | Partially verified                                    | Paid/unpaid handling and duplicate event IDs tested; wider lifecycle scenarios remain below.   |
| Deployment                     | Local topology verified; staging pending               | Validator, health gates, smoke test, and runbook pass locally; external services remain.        |
| Public paid launch             | Pending                                               | Complete the release gates below.                                                              |

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

- Resolved: Growth has no minimum by default. Platform Console → Pricing → Minimum billable employees controls this: 0 means no minimum; 1 or more sets a floor multiplied by the employee rate. New subscriptions snapshot the setting; existing snapshots are preserved. Missing historical minimum metadata defaults to zero.
- Annual Growth currently uses ten times the monthly unit rate. Confirm this commercial rule and disclose it before selling annual subscriptions.
- Editing the public free allowance affects Basic capacity globally. Existing Stripe subscriptions use their stored allowance. Decide whether existing Basic organizations also need a grandfathered allowance.
- Legacy Stripe subscriptions without allowance metadata are skipped by quantity synchronization. Do not infer or rewrite their commercial terms automatically.
- Quantity updates currently request proration. Verify actual invoice credits and charges in Stripe test mode.
- Checkout currently estimates the period end from the processing date. Verify and replace this with authoritative provider period data before relying on billing dates.

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
