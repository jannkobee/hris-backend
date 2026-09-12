# Organization setup

## First deployment

After migrations have completed, initialize the deployment's default workspace and create its first administrator:

```sh
php artisan platform:setup --admin-email=owner@example.com
```

The command privately prompts for a password and confirmation, initializes the permission catalogue, roles and HR defaults, and prints the login URL. Passwords require at least 12 characters, mixed case, a number and a symbol. It creates an administrator for the tenant workspace; Platform Console access remains controlled by the separate provisioning key.

On the current Lightsail staging deployment, after merging both repositories into main, pulling and rebuilding their images, use:

```sh
cd ~/suitify/hris-backend
sudo docker compose --env-file ../staging.env -f docker-compose.production.yml exec app php artisan platform:setup --admin-email=jkobe415@gmail.com
```

The command must exist in the deployed image before it can be used. Rebuilds are required because the PHP application source is baked into its images. No database reset or manual PHP account-creation script is needed.

Rerunning setup preserves existing accounts/passwords and does not create another owner when the workspace already has an active administrator. Existing customized default record values and nonempty User-role permission assignments are preserved. Empty default roles receive their initial permissions; the Admin role receives the complete catalogue. A disabled existing account is not silently reactivated or overwritten. Resolve that account through the established account-management workflow.

The migration-created default workspace must exist. To initialize another existing workspace explicitly:

```sh
php artisan platform:setup --organization=acme --admin-email=owner@example.com
```

## Later organizations

Use Platform Console → Organizations → Create organization. Enter its name, country, timezone, plan and owner email. The slug is suggested automatically and remains editable. Choose either:

- Invite the owner to set their own password (default).
- Set an initial password with confirmation.

The result includes the workspace login link. Invitation results distinguish submission to an email provider from a log/array transport or a send failure; operators can securely share the displayed private invitation link when mail is unavailable. Provider submission is not a guarantee of inbox delivery.

The CLI uses the same provisioning service:

```sh
php artisan organizations:create acme "Acme HR" --country=PH --timezone=Asia/Manila --plan=basic_free --admin-email=owner@example.com
```

This privately prompts for the password and creates the organization, administrator, roles, permissions, HR defaults and subscription history together. Free Basic defaults to active with no trial expiry. Other new plans default to a trial. The CLI requires an owner; duplicate slugs direct the operator to the existing-workspace setup command instead of suggesting a rerun that cannot work.

For automation, both commands accept `--admin-password-env=VARIABLE_NAME --no-interaction`. Supply the variable through your secret-management mechanism; do not put a literal password in shell history. The old `organizations:create --admin-password` option remains temporarily supported with a deprecation warning.

## Workspace addresses

Production Compose now builds the frontend with `/backend/api/v1` so API requests stay on the selected workspace hostname. Signup and invitation acceptance return a backend-generated login URL; their buttons navigate to it across hosts.

The default workspace uses the base frontend hostname. Other workspaces use `<slug>.<TENANT_BASE_DOMAIN>`. Configure DNS and TLS for those hostnames before distributing links. For local development without tenant hostnames, the existing default-tenant behavior remains; use tenant DNS configuration to test multiple workspaces.

On the current staging server, Caddy has a certificate for only the base hostname. For an organization named `acme`, add `acme.suitify.52-76-75-229.sslip.io` alongside the existing hostname in the Caddy site-address list and reload after validating the file. Each sslip.io tenant hostname needs its own certificate; wildcard TLS is not supplied by that DNS service. The application does not provision AWS firewall rules or certificates automatically.

## Baseline and demo seeding

`DatabaseSeeder` is now credential-free. It initializes catalogue/default data but does not create an administrator. Use `platform:setup` for administrator bootstrap. Direct `AdminSeeder` execution is restricted to local/testing environments because it contains known demo credentials. This change does not remove any demo account seeded before the update.

Keep deployment secrets outside both source/build directories, as with `~/suitify/staging.env`. Docker ignore rules also exclude nested environment files while retaining the production example template.

## Acceptance after rollout

Verify setup on the staging image, log in as its owner, and open employee/settings screens. For a second organization, verify its invitation or signup, tenant hostname/TLS, correct login destination and access isolation. Email delivery, backups/restore, monitoring and rollback remain separate deployment checks. The full staging validator also requires SMTP and Stripe credentials; a log-mail, billing-disabled smoke environment does not satisfy that full provider gate.
