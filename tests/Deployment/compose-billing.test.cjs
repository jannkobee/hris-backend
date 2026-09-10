const assert = require("node:assert/strict");
const { spawnSync } = require("node:child_process");
const { mkdtempSync, writeFileSync, rmSync } = require("node:fs");
const { tmpdir } = require("node:os");
const path = require("node:path");
const { test } = require("node:test");

const root = path.resolve(__dirname, "../..");
const billing = {
    STRIPE_SECRET_KEY: "sk_test_compose_fixture",
    STRIPE_WEBHOOK_SECRET: "whsec_compose_fixture",
    BILLING_PORTAL_RETURN_HOSTS: "staging.example.test,tenant.example.test",
};
const fixture = {
    DOMAIN: "staging.example.test",
    APP_KEY: "compose-fixture",
    DB_DATABASE: "fixture",
    DB_USERNAME: "fixture",
    DB_PASSWORD: "fixture",
    DB_ROOT_PASSWORD: "fixture",
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
    PLATFORM_PROVISIONING_KEY: "platform-compose-fixture",
};

for (const [file, services] of [
    ["docker-compose.production.yml", ["app", "queue", "scheduler", "migrate"]],
    [
        "docker-compose.yml",
        ["backend", "queue", "scheduler", "migrate", "reverb"],
    ],
]) {
    for (const configured of [true, false]) {
        test(`${file}: billing ${configured ? "configured" : "disabled"}`, () => {
            const directory = mkdtempSync(
                path.join(tmpdir(), "hris-compose-test-"),
            );
            try {
                const envFile = path.join(directory, "fixture.env");
                const values = { ...fixture, ...(configured ? billing : {}) };
                writeFileSync(
                    envFile,
                    Object.entries(values)
                        .map(([key, value]) => `${key}=${value}`)
                        .join("\n"),
                );
                const env = { ...process.env };
                // Do not allow host secrets or Compose settings to override the fixture.
                for (const key of Object.keys(env)) {
                    if (
                        key in fixture ||
                        key in billing ||
                        /^(COMPOSE_|DOCKER_)/.test(key)
                    )
                        delete env[key];
                }
                const result = spawnSync(
                    "docker",
                    [
                        "compose",
                        "--env-file",
                        envFile,
                        "-f",
                        file,
                        "config",
                        "--format",
                        "json",
                    ],
                    { cwd: root, env, encoding: "utf8", timeout: 30000 },
                );
                assert.equal(
                    result.status,
                    0,
                    "Docker Compose configuration rendering must succeed",
                );
                const config = JSON.parse(result.stdout);
                const expected = configured
                    ? billing
                    : {
                          STRIPE_SECRET_KEY: "",
                          STRIPE_WEBHOOK_SECRET: "",
                          BILLING_PORTAL_RETURN_HOSTS: "localhost",
                      };
                for (const service of services) {
                    for (const [key, value] of Object.entries(expected)) {
                        assert.equal(
                            config.services[service].environment[key],
                            value,
                            `${service}: ${key}`,
                        );
                    }
                }
                for (const [name, service] of Object.entries(config.services)) {
                    if (services.includes(name)) continue;
                    for (const key of Object.keys(billing)) {
                        assert.equal(
                            service.environment?.[key],
                            undefined,
                            `${name} must not receive ${key}`,
                        );
                        assert.equal(
                            service.build?.args?.[key],
                            undefined,
                            `${name} build must not receive ${key}`,
                        );
                    }
                }
                if (file === "docker-compose.production.yml") {
                    assert.equal(
                        config.services.app.environment
                            .PLATFORM_PROVISIONING_KEY,
                        fixture.PLATFORM_PROVISIONING_KEY,
                        "app: PLATFORM_PROVISIONING_KEY",
                    );
                    for (const [name, service] of Object.entries(
                        config.services,
                    )) {
                        if (name === "app") continue;
                        assert.equal(
                            service.environment?.PLATFORM_PROVISIONING_KEY,
                            undefined,
                            `${name} must not receive PLATFORM_PROVISIONING_KEY`,
                        );
                    }
                }
            } finally {
                rmSync(directory, { recursive: true, force: true });
            }
        });
    }
}
