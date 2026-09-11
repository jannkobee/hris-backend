#!/usr/bin/env sh

set -eu

script_dir=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
project_root=$(dirname "$script_dir")
frontend_root=$(dirname "$project_root")/hris-frontend
frontend_port=${SMOKE_FRONTEND_PORT:-18080}
reverb_port=${SMOKE_REVERB_PORT:-18081}
project_name="suitify-hr-smoke-$$"
temporary_dir=$(mktemp -d)
environment_file="$temporary_dir/staging.env"

cleanup() {
    docker compose --project-name "$project_name" --env-file "$environment_file" \
        -f "$project_root/docker-compose.production.yml" down --volumes --remove-orphans >/dev/null 2>&1 || true
    rm -rf "$temporary_dir"
}
trap cleanup EXIT INT TERM

for command_name in docker node php curl; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "Required command is unavailable: $command_name" >&2
        exit 1
    fi
done

# The smoke path favors compatibility over BuildKit-only optimizations. Callers can
# explicitly opt back into BuildKit after confirming their Compose/Buildx setup.
export DOCKER_BUILDKIT=${DOCKER_BUILDKIT:-0}
export COMPOSE_DOCKER_CLI_BUILD=${COMPOSE_DOCKER_CLI_BUILD:-0}

if [ ! -d "$frontend_root" ]; then
    echo "Expected sibling frontend repository at: $frontend_root" >&2
    exit 1
fi

umask 077
{
    echo 'DOMAIN=smoke.suitify-hr.test'
    echo 'TENANT_BASE_DOMAIN=smoke.suitify-hr.test'
    echo 'APP_KEY=base64:YWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWE='
    echo 'AUDIT_LOG_SIGNING_KEY=bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'
    echo 'PLATFORM_PROVISIONING_KEY=cccccccccccccccccccccccccccccccc'
    echo 'DB_DATABASE=suitify_hr_smoke'
    echo 'DB_USERNAME=suitify_hr_smoke'
    echo 'DB_PASSWORD=dddddddddddddddddddd'
    echo 'DB_ROOT_PASSWORD=eeeeeeeeeeeeeeeeeeee'
    echo 'MYSQL_IMAGE=mysql:8.4'
    echo 'REDIS_IMAGE=redis:7-alpine'
    echo 'MAIL_MAILER=smtp'
    echo 'MAIL_HOST=smtp.smoke.test'
    echo 'MAIL_PORT=587'
    echo 'MAIL_USERNAME=smoke-user'
    echo 'MAIL_PASSWORD=smoke-mail-password'
    echo 'MAIL_ENCRYPTION=tls'
    echo 'MAIL_FROM_ADDRESS=no-reply@smoke.suitify-hr.test'
    echo 'REVERB_APP_ID=smoke-reverb'
    echo 'REVERB_APP_KEY=ffffffffffffffff'
    echo 'REVERB_APP_SECRET=gggggggggggggggggggggggggggggggg'
    echo 'FRONTEND_BIND_ADDRESS=127.0.0.1'
    echo "FRONTEND_PORT_FORWARD=$frontend_port"
    echo 'REVERB_BIND_ADDRESS=127.0.0.1'
    echo "REVERB_PORT_FORWARD=$reverb_port"
    echo 'STRIPE_SECRET_KEY=sk_test_smoke_fixture'
    echo 'STRIPE_WEBHOOK_SECRET=whsec_smoke_fixture'
    echo 'BILLING_PORTAL_RETURN_HOSTS=smoke.suitify-hr.test,acme.smoke.suitify-hr.test'
    echo 'LOG_LEVEL=warning'
} > "$environment_file"

compose() {
    docker compose --project-name "$project_name" --env-file "$environment_file" \
        -f "$project_root/docker-compose.production.yml" "$@"
}

cd "$project_root"
php artisan deployment:validate-staging --env-file="$environment_file"
node tests/Deployment/compose-billing.test.cjs
compose config --quiet
compose up -d --build

attempt=0
until curl --fail --silent --show-error "http://127.0.0.1:$frontend_port/healthz" >/dev/null \
    && curl --fail --silent --show-error -H 'Host: smoke.suitify-hr.test' \
        "http://127.0.0.1:$frontend_port/backend/api/v1/health" >/dev/null; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 60 ]; then
        compose ps
        compose logs --tail=100
        echo 'Local deployment smoke test timed out.' >&2
        exit 1
    fi
    sleep 2
done

for service in mysql redis app api queue scheduler reverb frontend; do
    if ! compose ps --status running --services | grep -qx "$service"; then
        compose ps
        echo "Service is not running: $service" >&2
        exit 1
    fi
done

compose exec -T app php artisan migrate:status >/dev/null
compose exec -T app php artisan tenancy:audit
compose exec -T app php artisan queue:failed
compose exec -T app php artisan schedule:list >/dev/null

echo 'Local production-topology smoke test passed.'
