# Production deployment

Copy `deploy/.env.production.example` to a secure host-only `deploy/.env.production`, replace every placeholder, and generate `APP_KEY` with `php artisan key:generate --show`.

From `hris-backend`, deploy with:

```sh
docker compose --env-file deploy/.env.production -f docker-compose.production.yml up -d --build
```

Set `PLATFORM_PROVISIONING_KEY` to a unique long random value. Production Compose forwards it only to the HTTP application service; it is intentionally absent from migration, worker, scheduler, Reverb, and frontend containers.

Before starting any staging deployment, validate the host-only environment file without printing secret values:

```sh
php artisan deployment:validate-staging --env-file=deploy/.env.production
```

Production Compose binds the frontend to `${FRONTEND_BIND_ADDRESS:-127.0.0.1}:${FRONTEND_PORT_FORWARD:-8081}` and Reverb to `${REVERB_BIND_ADDRESS:-127.0.0.1}:${REVERB_PORT_FORWARD:-8082}`. Place a TLS reverse proxy (Caddy, Traefik, or managed load balancer) in front of those ports. Route ordinary HTTPS traffic to the frontend port and WebSocket requests under `/app/*` to the Reverb port. Do not expose MySQL or Redis. Back up the `mysql-data` and `backend-storage` volumes, store production secrets in a secret manager, and run `php artisan tenancy:audit` after each release.

MySQL, Redis, PHP-FPM, API Nginx, queue, scheduler, Reverb, and frontend services have container health checks. Startup dependencies wait for the relevant upstream service to become healthy. Check them with:

```sh
docker compose --env-file deploy/.env.production -f docker-compose.production.yml ps
```

See [infrastructure requirements](infrastructure-requirements.md) for DNS, TLS, sizing, storage, secret, monitoring, and topology requirements.

## Billing configuration

Set `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`, and `BILLING_PORTAL_RETURN_HOSTS` in the deployment environment file. The return hosts are comma-separated frontend hostnames without schemes or paths. Both Compose files forward these settings to the PHP application, queue, scheduler, and migration services; they are not frontend build arguments. Blank Stripe secrets leave billing unconfigured so core HR can still run.

For isolated staging, use a separate environment file with Stripe test-mode credentials and the staging frontend hostname. Register `/backend/api/v1/billing/stripe/webhook` on the staging HTTPS API host. Recreate the backend services after changing runtime settings; a host environment-file edit alone does not update running containers.

Run the configuration regression check from `hris-backend` with Docker Compose and Node installed:

```sh
node --test tests/Deployment/compose-billing.test.cjs tests/Deployment/compose-readiness.test.cjs
```

This check renders both Compose configurations with dummy values and verifies service inheritance and disabled-billing defaults. It does not start containers, run migrations, or contact Stripe. Staging still needs runtime credential, checkout, webhook, portal, queue, and scheduler verification. Avoid sharing rendered Compose output from a real environment because it contains secrets.

## Disposable local deployment smoke test

Run the entire production topology locally with generated dummy settings:

```sh
bash deploy/smoke-local.sh
```

This command builds a uniquely named disposable Compose project, waits for frontend and API health, verifies migrations, tenancy, failed-job access and the scheduler, and then removes its containers and volumes. It does not contact Stripe or SMTP. If necessary, select unused ports with `SMOKE_FRONTEND_PORT` and `SMOKE_REVERB_PORT`.
