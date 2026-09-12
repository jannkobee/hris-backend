# Organization creation review — 2026-09-12

**Follow-up implementation:** Secure `platform:setup`, shared CLI/web initialization, owner validation, credential-free baseline seeding, preserved defaults on retries, tenant-aware login links, same-origin production API routing, and Console invitation controls are now implemented locally. See [Organization setup](organization-setup.md). Findings below record the reviewed pre-change behavior. AWS rollout and browser/provider acceptance remain pending.

Scope: fresh deployment bootstrap, CLI organization creation, public signup, Platform Console provisioning, owner invitations, and workspace login routing. This is a review; no application behavior has been changed or deployed.

## Findings

### P1 — New organizations cannot reliably reach their own login

`docker-compose.production.yml:185` builds the frontend with an absolute API URL on the base hostname. `src/plugins/axios.ts:68` in the frontend uses that URL unchanged. `TenantResolver.php:31` resolves that base hostname to the default `legacy` organization. Public signup creates a different organization, but `StartTrial.vue` links its success button to the same `/login` and returns only the new slug in its success message.

Consequently, a new organization's administrator attempts authentication in the default organization. Merely opening a tenant frontend subdomain does not fix the compiled API destination. The deployed Caddy configuration also currently covers only the base hostname.

Fix: define one supported workspace-address strategy. For the existing tenant-host architecture, use a same-origin API path, generate the correct workspace/login URL on the backend, and make successful creation lead to that URL. Include explicit hostname/TLS readiness; sslip.io needs individual tenant certificates. Verify two organizations in a production-style proxy deployment, including identical email addresses across tenants.

### P1 — Platform validation permits an organization without any usable owner

`ProvisionOrganizationRequest.php:33` uses `required_without:send_owner_invitation` for the password. Laravel treats the boolean false as a present value, so an explicit false flag suppresses the password requirement. `OrganizationProvisioningService.php` creates an administrator only when both email and password are filled; the controller sends an invitation only when the flag is strictly true.

Reproduction using the installed Laravel validator: omitted password is accepted when the invitation flag is false or true, but rejected when the flag is absent. A false flag therefore permits an ownerless organization. The API also accepts boolean-like values but the controller uses strict identity, so boolean normalization needs explicit coverage.

Fix: represent owner setup as an explicit mode (`invite` or `set_password`), normalize input, and enforce one valid owner path in the shared service as well as request validation. Reject ambiguous combinations. An invitation setup failure must leave a recoverable status rather than a misleading completed result.

### P1 — The normal database seeder creates public development credentials

`DatabaseSeeder.php` calls `AdminSeeder.php:28`, which creates `admin@base.com` with password `secret`. There is no environment guard. Running the usual `db:seed --force` on a fresh public staging or production host introduces that administrator.

Fix: make baseline seeding credential-free. Put demo identities behind explicit local/testing-only behavior. Provide a supported bootstrap command with private password prompts or an owner invitation; never require operators to edit passwords inside PHP or pass them in shell arguments.

### P2 — CLI provisioning diverges from the web service and its recovery advice cannot work

`CreateOrganization.php` duplicates the organization, roles, administrator and defaults logic instead of calling `OrganizationProvisioningService`. It does not apply the web password/email rules, accepts passwords as command-line arguments, and does not establish the same trial/subscription lifecycle record. Country is implicitly supplied by the database's PH default, and timezone is implicit rather than an explicit creation choice.

The command permits creation without an administrator, then advises rerunning with administrator flags at line 97. The duplicate-slug check at line 52 rejects that rerun. The migration-created default organization also cannot be initialized through this command, which is why staging needed an ad hoc account script.

Fix: use a shared service and shared validation. Separate fresh `organizations:create` from an explicit initialize/repair operation for an existing workspace. A retry must preserve existing users/passwords and data, and report what it initialized. Provide masked password confirmation and organization-scoped owner creation.

### P2 — New-organization defaults depend on out-of-band seeding

Both provisioning paths assign whatever permissions already exist in the global table. Neither establishes or checks the permission catalogue. `OrganizationDefaultsSeeder` does not seed it. They also create a User role without applying `permissions.default_roles`, unlike `RolePermissionSeeder`.

This can report provisioning success on a fresh database while role assignments are incomplete. The Admin role's permission bypass can hide this from an administrator; permissions payloads and ordinary users still need correct assignments. Existing provisioning tests manually insert a permission, masking the missing bootstrap contract.

Fix: one idempotent initialization service should synchronize the catalogue and initialize organization-scoped roles, role permissions and HR defaults in an explicit order. Test both a completely migrated/unseeded database and a populated organization being repaired. Preserve customized assignments according to a documented policy.

### P2 — Invitation success does not establish email delivery or working login

`OrganizationOwnerInvitationService.php:54` treats any non-throwing mail send as `mail_delivered: true`. With the current log mailer, the message is only logged, yet the response reports delivery. Invitation acceptance and signup do not supply a tenant-aware login destination. The Platform Console creation screen requires a temporary password despite an invitation API already existing, so the easier owner-controlled password flow is unavailable there.

Fix: expose invitation setup in the creation screen; distinguish pending, submitted-to-mail-transport, failed and accepted states. A log/array transport cannot establish delivery. Provide an authorized copy-invitation-link action for staging, retry/resend behavior, and the correct workspace login URL after acceptance. Keep SMTP provider acceptance distinct from inbox delivery.

## Proposed operator experience

1. First deployment: one `platform:setup` command initializes baseline data and the existing default workspace, then securely provisions its administrator. It prints the login URL and outstanding infrastructure checks. This command is proposed, not currently implemented.
2. Later organizations: Platform Console → Create organization → name, country, timezone, plan, owner email → invite owner or set password privately. Slug generation and subscription defaults are automatic but editable where appropriate.
3. Result: workspace URL, owner setup state, and a clear Open workspace action. If DNS/TLS or mail is incomplete, show that step as pending with a recoverable action.
4. CLI, public signup, Console and bootstrap all call the same initialization/provisioning services. Never seed a fixed password as part of ordinary deployment.

Implementation order: remove unsafe bootstrap credentials and add supported idempotent setup; unify creation and validation; correct tenant-aware routing; expose invitation workflow and readiness states; verify complete fresh-install and two-tenant journeys.

## Verification and limits

- `php artisan test --filter='OrganizationProvisioningTest|TrialSignupTest|OrganizationOwnerInvitationTest'`: 6 passed, 35 assertions, using the configured isolated SQLite in-memory test database.
- A standalone validator probe against installed dependencies reproduced the false-invitation/missing-password acceptance. Probe retained at workspace `.tmp/org-review-validation.php`.
- Routing, CLI and seeder findings are source-reviewed. No customer data was changed and no API defect was exercised against the public staging database.
- Existing tests cover selected service/API successes, serial invitation reuse, and slug collisions; they do not establish fresh-install bootstrap, default-role completeness, CLI retry behavior, or a browser signup-to-tenant-login journey.
- Previously reported staging tenancy, authorization and encryption audits passed; they do not test these provisioning behavior gaps.
