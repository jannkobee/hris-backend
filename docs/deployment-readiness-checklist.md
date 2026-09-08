# Deployment Readiness & Operations Runbook

Ensure all operational primitives are configured and verified before promoting LexisOne to public production.

---

## 1. Database Migrations & Multi-Tenant Audit

-   [ ] **Run Migrations**:
    ```bash
    php artisan migrate --force
    ```
-   [ ] **Tenant Isolation Audit**:
        Verify all company-owned tables enforce `organization_id` isolation:
    ```bash
    php artisan tenancy:audit
    ```
    Expected output: `Tenant schema audit passed for 69 tables.`

---

## 2. Background Queue Workers

-   [ ] **Queue Connection**: Verify `QUEUE_CONNECTION` (default `database` or `redis`).
-   [ ] **Supervisor / Worker Process**:
    ```bash
    php artisan queue:work --sleep=3 --tries=3 --max-time=3600
    ```
-   [ ] **Failed Jobs Table**:
        Check `failed_jobs` table is migrated and healthy:
    ```bash
    php artisan queue:failed
    ```

---

## 3. Scheduled Tasks & Cron Runner

-   [ ] **Crontab Entry**:
        Verify the server crontab contains the 1-minute runner:
    ```cron
    * * * * * cd /path/to/hris-backend && php artisan schedule:run >> /dev/null 2>&1
    ```
-   [ ] **Scheduled Jobs Overview**:
    -   `reports:deliver`: Runs hourly to email scheduled reports.
    -   `subscriptions:reconcile`: Runs daily at 01:00 to reconcile trial/subscription statuses and sync Stripe Growth quantities.
    -   `audit-logs:verify`: Runs daily at 01:30 to verify SHA-256 tamper-proof log hashes.
    -   `training:send-expiry-reminders`: Runs daily at 08:00.

---

## 4. Mail & Notification Delivery

-   [ ] **Mail Driver**: Confirm `MAIL_MAILER=smtp` (or `ses`) with valid host, port, user, and TLS credentials.
-   [ ] **Test Delivery**: Send test email and verify DKIM/SPF passing:
    ```bash
    php artisan tinker --execute="Mail::raw('LexisOne staging ping', function(\$m) { \$m->to('admin@example.com')->subject('Delivery Test'); });"
    ```

---

## 5. Database Backup and Test Restoration

-   [ ] **Automated SQL Backup**:
        Generate compressed timestamped dump:

    ```bash
    php artisan db:backup
    ```

    Verify backup file is written to `storage/app/backups/lexisone_backup_*.sql.gz` and recorded in `platform_operation_logs`.

-   [ ] **Test Database Restoration**:
        Verify backup can be cleanly decompressed and loaded with foreign keys restored:
    ```bash
    php artisan db:restore lexisone_backup_YYYY_MM_DD_HHMMSS.sql.gz --force
    ```
    Verify output displays:
    ```
    Database successfully restored from backup.
    Tenant schema audit passed for 69 tables.
    ```

---

## 6. Stripe Webhook & Secret Provisioning

-   [ ] **Webhook Endpoint**: Register `https://your-domain.com/api/billing/stripe/webhook` in the Stripe Dashboard.
-   [ ] **Event Subscriptions**:
    -   `checkout.session.completed`
    -   `customer.subscription.updated`
    -   `customer.subscription.deleted`
    -   `invoice.payment_failed`
-   [ ] **Secrets Verification**:
    -   Set `STRIPE_SECRET_KEY` and `STRIPE_WEBHOOK_SECRET` in production `.env`.
