# Production deployment

Copy `deploy/.env.production.example` to a secure host-only `.env.production`, replace every placeholder, and generate `APP_KEY` with `php artisan key:generate --show`.

From `hris-backend`, deploy with:

```sh
docker compose --env-file deploy/.env.production -f docker-compose.production.yml up -d --build
```

Place a TLS reverse proxy (Caddy, Traefik, or managed load balancer) in front of port 80. Do not expose MySQL or Redis. Back up the `mysql-data` and `backend-storage` volumes, store production secrets in a secret manager, and run `php artisan tenancy:audit` after each release.
