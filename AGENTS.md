# Backend coding baseline

- After each implementation task, update both `docs/industry-readiness-roadmap.md`
  and `docs/saas-launch-roadmap.md` with delivered scope, verification evidence,
  remaining issues, and the next step. Distinguish implementation from staging verification.
-   Follow the mandatory autonomous session startup and conclusion protocols defined in the root AGENTS.md.
-   At the start of every session, read `docs/session-logs.md`, `docs/module-improvement-roadmap.md`, `docs/industry-readiness-roadmap.md`, and `docs/saas-launch-roadmap.md` without waiting for a user prompt.
-   After each implementation task, log the session details in `docs/session-logs.md` and update both `docs/industry-readiness-roadmap.md` and `docs/saas-launch-roadmap.md` with delivered scope, verification evidence, remaining issues, and next steps. Distinguish implementation from staging verification.

Apply these conventions to every new or edited backend feature.

- Preserve tenant isolation. Every company-owned Eloquent model must use
  `BelongsToOrganization`; global catalogue data such as `Permission` is the
  deliberate exception. Add every company-owned table to
  `tenancy.owned_tables`, and run `php artisan tenancy:audit` after schema
  changes. Do not use `DB::table()` for tenant data without an explicit
  `organization_id` condition.
- Place API endpoints in the existing module file under `routes/api/`.
  Use `Route::apiResource()` for conventional CRUD. For custom endpoints,
  group related routes with `prefix()`, `name()`, and `controller()` and use
  action strings within that group. Do not repeat `[Controller::class, ...]`
  for every child route.
- Keep API URLs and route names backwards-compatible unless the task calls
  for an API-versioned breaking change.
- Validate request input in Form Requests, keep controllers thin, and place
  persistence/business workflows in the existing repository or service layer.
- Background commands and scheduled tasks must explicitly iterate active
  organizations and run each unit of work inside `TenantContext::run()`.
- Use UUIDs and existing response/error conventions. Add focused feature
  tests for authorization, tenant isolation, and any changed workflow.
-   Preserve tenant isolation. Every company-owned Eloquent model must use
    `BelongsToOrganization`; global catalogue data such as `Permission` is the
    deliberate exception. Add every company-owned table to
    `tenancy.owned_tables`, and run `php artisan tenancy:audit` after schema
    changes. Do not use `DB::table()` for tenant data without an explicit
    `organization_id` condition.
-   Place API endpoints in the existing module file under `routes/api/`.
    Use `Route::apiResource()` for conventional CRUD. For custom endpoints,
    group related routes with `prefix()`, `name()`, and `controller()` and use
    action strings within that group. Do not repeat `[Controller::class, ...]`
    for every child route.
-   Keep API URLs and route names backwards-compatible unless the task calls
    for an API-versioned breaking change.
-   Validate request input in Form Requests, keep controllers thin, and place
    persistence/business workflows in the existing repository or service layer.
-   Background commands and scheduled tasks must explicitly iterate active
    organizations and run each unit of work inside `TenantContext::run()`.
-   Use UUIDs and existing response/error conventions. Add focused feature
    tests for authorization, tenant isolation, and any changed workflow.
