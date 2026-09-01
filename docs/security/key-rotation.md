# Encryption Key Rotation Runbook

Laravel encrypted casts use `APP_KEY`. Rotating this key without a compatibility plan makes existing encrypted values unreadable.

1. Schedule maintenance and take a verified encrypted database backup.
2. Confirm `php artisan security:encryption-audit` passes and record the current key in the production secret manager.
3. Deploy with the existing key available as a temporary previous decryption key, following the Laravel version and deployment platform's supported key-rotation mechanism.
4. Re-save every registered encrypted field while the old key remains available so Laravel writes it with the new key.
5. Verify MFA, OIDC login, SCIM, webhook delivery, and protected employee-document retrieval in a staging tenant.
6. Run `php artisan audit-logs:verify` and `php artisan security:encryption-audit` after deployment.
7. Retire the previous key only after the re-encryption job and restore test succeed.

Never rotate `APP_KEY` ad hoc in a running production environment. For production, keep keys in a secret manager with access auditing and emergency rollback support.
