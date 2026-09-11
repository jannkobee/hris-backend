# Suitify HR infrastructure requirements

This document describes the infrastructure needed to move from local verification to isolated staging and, later, production. Staging and production must use separate credentials, databases, storage, email accounts, payment-provider environments, and DNS names.

## Minimum external requirements

| Capability | Staging requirement | Production direction |
| --- | --- | --- |
| Compute | One Linux VM with Docker Engine and Docker Compose | Redundant application capacity or a documented replacement/recovery time |
| DNS | Base record plus wildcard tenant record | Managed DNS with change control and DNSSEC where supported |
| TLS | Certificate covering the base and wildcard tenant domains | Automated renewal and expiry alerting |
| Database | Dedicated MySQL 8.4 data and credentials | Managed MySQL or encrypted backups with tested restoration |
| Cache and queue | Dedicated Redis instance/container | Private Redis with persistence/availability selected to match recovery objectives |
| Email | Isolated SMTP credentials and a verified sender domain | SPF, DKIM, DMARC, bounce monitoring, and delivery alerts |
| Billing | Stripe test-mode account and endpoint secret | Approved supported payment provider and live credentials held outside source control |
| Backups | Off-server database and private-file copies | Encrypted, versioned, access-controlled storage with retention policy |
| Secrets | Unique generated keys in a host-only file or secret manager | Managed secret store, rotation owners, and recovery procedure |
| Monitoring | Container health, logs, failed jobs, and external HTTPS checks | Alerts with an accountable responder and an incident runbook |

No customer or employee production data should be placed in staging.

## Estimated single-host staging topology

Start with approximately 2 vCPU, 4 GB RAM, and 40 GB of SSD storage, then measure image-build memory, database size, request latency, queue delay, and disk growth. This is an initial engineering estimate, not a capacity guarantee.

```text
Internet
   |
DNS + wildcard TLS
   |
TLS reverse proxy / load balancer
   |-- ordinary HTTPS ----------> 127.0.0.1:8081 -> frontend -> api -> app
   `-- WebSocket /app/* --------> 127.0.0.1:8082 -> reverb

Private Compose network
   |-- mysql       persistent mysql-data
   |-- redis       persistent redis-data
   |-- queue
   |-- scheduler
   `-- app         persistent backend-storage

Off host
   |-- SMTP provider
   |-- Stripe test mode
   `-- encrypted backup storage
```

The production Compose defaults bind frontend and Reverb ports to `127.0.0.1`; only the TLS proxy should accept public traffic. Never publish MySQL or Redis ports.

## DNS and routing

For a staging base domain such as `staging.example.test`, configure the base name and `*.staging.example.test` to reach the TLS proxy. The certificate must cover both names. Route ordinary requests to `FRONTEND_PORT_FORWARD` and WebSocket upgrade requests under `/app/*` to `REVERB_PORT_FORWARD`.

`BILLING_PORTAL_RETURN_HOSTS` is an exact allowlist, not a wildcard. Add the base hostname and each tenant hostname that needs to return from the payment-provider portal.

## Secret ownership

Generate unique values for `APP_KEY`, `AUDIT_LOG_SIGNING_KEY`, `PLATFORM_PROVISIONING_KEY`, database passwords, and the Reverb secret. Do not reuse values between environments or between purposes. Keep the environment file mode at `0600`, never commit it, and do not paste rendered Compose configuration into tickets or chat because it contains resolved secrets.

Use `php artisan deployment:validate-staging --env-file=deploy/.env.production` before deployment. The command reports key names and validation problems without displaying secret values.

## Local production-topology rehearsal

With Docker, Node, PHP, curl, and the sibling `hris-frontend` repository available, run:

```sh
bash deploy/smoke-local.sh
```

The script creates a disposable Compose project with dummy staging-only settings, builds the production images, waits for HTTP and API health, verifies migrations, tenancy, queue visibility, and the schedule, then removes its containers and volumes. Override `SMOKE_FRONTEND_PORT` and `SMOKE_REVERB_PORT` if ports 18080 or 18081 are already occupied.

This rehearsal does not verify public DNS, TLS, external SMTP delivery, provider webhooks, backup durability, browser workflows, or rollback on real infrastructure.
