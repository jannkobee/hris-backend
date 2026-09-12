const assert = require("node:assert/strict");
const { spawnSync } = require("node:child_process");
const { readFileSync } = require("node:fs");
const path = require("node:path");
const { test } = require("node:test");

const root = path.resolve(__dirname, "../..");

test("production topology uses private ports, health checks, and dependency gates", () => {
    const env = {
        ...process.env,
        DOMAIN: "staging.example.test",
        APP_KEY: "compose-app-key",
        AUDIT_LOG_SIGNING_KEY: "compose-audit-key",
        PLATFORM_PROVISIONING_KEY: "compose-platform-key",
        DB_DATABASE: "fixture",
        DB_USERNAME: "fixture",
        DB_PASSWORD: "fixture-password",
        DB_ROOT_PASSWORD: "fixture-root-password",
        MYSQL_IMAGE: "mysql:8.4",
        REDIS_IMAGE: "redis:7-alpine",
        TENANT_BASE_DOMAIN: "staging.example.test",
        MAIL_MAILER: "log",
        MAIL_HOST: "localhost",
        MAIL_PORT: "1025",
        MAIL_USERNAME: "fixture",
        MAIL_PASSWORD: "fixture",
        MAIL_ENCRYPTION: "",
        MAIL_FROM_ADDRESS: "fixture@example.test",
        FRONTEND_BIND_ADDRESS: "127.0.0.1",
        FRONTEND_PORT_FORWARD: "18080",
        REVERB_BIND_ADDRESS: "127.0.0.1",
        REVERB_PORT_FORWARD: "18081",
    };
    const result = spawnSync(
        "docker",
        [
            "compose",
            "-f",
            "docker-compose.production.yml",
            "config",
            "--format",
            "json",
        ],
        { cwd: root, env, encoding: "utf8", timeout: 30000 },
    );
    assert.equal(result.status, 0, result.stderr);

    const config = JSON.parse(result.stdout);
    // Login and API calls must stay on the selected tenant hostname.
    assert.equal(config.services.frontend.build.args.VITE_API_URL, "/backend/api/v1");
    assert.deepEqual(config.services.frontend.ports[0], {
        mode: "ingress",
        host_ip: "127.0.0.1",
        target: 80,
        published: "18080",
        protocol: "tcp",
    });
    assert.deepEqual(config.services.reverb.ports[0], {
        mode: "ingress",
        host_ip: "127.0.0.1",
        target: 8080,
        published: "18081",
        protocol: "tcp",
    });

    for (const service of [
        "mysql",
        "redis",
        "app",
        "api",
        "queue",
        "scheduler",
        "reverb",
        "frontend",
    ]) {
        assert.ok(config.services[service].healthcheck, `${service} healthcheck`);
    }

    assert.equal(config.services.app.depends_on.redis.condition, "service_healthy");
    assert.equal(config.services.api.depends_on.app.condition, "service_healthy");
    assert.equal(config.services.frontend.depends_on.api.condition, "service_healthy");
    assert.equal(config.services.reverb.depends_on.redis.condition, "service_healthy");

    assert.match(
        readFileSync(path.join(root, "deploy/nginx-api.conf"), "utf8"),
        /location = \/healthz/,
    );
    assert.match(
        readFileSync(path.join(root, "deploy/frontend-nginx.conf"), "utf8"),
        /location = \/healthz/,
    );
});
