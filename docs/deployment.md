# Production deployment

Copy `deploy/.env.production.example` to a secure host-only `deploy/.env.production`, replace every placeholder, and generate `APP_KEY` with `php artisan key:generate --show`.

From `hris-backend`, deploy with:

```sh
docker compose --env-file deploy/.env.production -f docker-compose.production.yml up -d --build
```

Place a TLS reverse proxy (Caddy, Traefik, or managed load balancer) in front of port 80. Do not expose MySQL or Redis. Back up the `mysql-data` and `backend-storage` volumes, store production secrets in a secret manager, and run `php artisan tenancy:audit` after each release.

## Billing configuration

Set `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`, and `BILLING_PORTAL_RETURN_HOSTS` in the deployment environment file. The return hosts are comma-separated frontend hostnames without schemes or paths. Both Compose files forward these settings to the PHP application, queue, scheduler, and migration services; they are not frontend build arguments. Blank Stripe secrets leave billing unconfigured so core HR can still run.

For isolated staging, use a separate environment file with Stripe test-mode credentials and the staging frontend hostname. Register `/backend/api/v1/billing/stripe/webhook` on the staging HTTPS API host. Recreate the backend services after changing runtime settings; a host environment-file edit alone does not update running containers.

Run the configuration regression check from `hris-backend` with Docker Compose and Node installed:

```sh
node --test tests/Deployment/compose-billing.test.cjs
```

This check renders both Compose configurations with dummy values and verifies service inheritance and disabled-billing defaults. It does not start containers, run migrations, or contact Stripe. Staging still needs runtime credential, checkout, webhook, portal, queue, and scheduler verification. Avoid sharing rendered Compose output from a real environment because it contains secrets.
