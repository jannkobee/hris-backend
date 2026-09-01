# Security Policy

## Reporting a vulnerability

Do not create a public issue for a suspected security vulnerability. Send the report to the security contact configured by the HRISFlow operator, including reproduction steps, impact, and affected endpoints. Acknowledge reports within two business days and provide a remediation target after triage.

## Supported deployment baseline

- Keep Laravel, PHP, Composer dependencies, and frontend dependencies patched.
- Use HTTPS, secure cookies, a production CSP, and an external secret manager.
- Enable MFA for privileged users and review active sessions regularly.
- Run `php artisan authorization:audit --strict`, `php artisan security:encryption-audit`, and `php artisan audit-logs:verify` in deployment operations.
- Run restore drills and preserve audit records according to the configured retention policy.

## Sensitive data

The application encrypts MFA credentials, OIDC client secrets, webhook signing secrets, and employee-document identifiers/notes at rest. Never write plaintext credentials, tokens, or government identifiers to application logs or support tickets.
