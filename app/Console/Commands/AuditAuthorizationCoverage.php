<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;

class AuditAuthorizationCoverage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'authorization:audit {--strict : Return a failure code when reviewed self-service routes are not explicitly registered}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Report authenticated tenant API endpoints that lack explicit permission middleware.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $unreviewed = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => str_starts_with($route->uri(), 'backend/api/v1/'))
            ->filter(fn (Route $route): bool => $this->hasMiddleware($route, ['auth:sanctum', 'Authenticate:sanctum']))
            ->reject(fn (Route $route): bool => $this->hasMiddleware($route, ['permission:', 'EnsurePermission']))
            ->reject(fn (Route $route): bool => $this->hasControllerAuthorization($route))
            ->map(fn (Route $route): array => [
                'method' => implode('|', $route->methods()),
                'uri' => $route->uri(),
                'action' => $route->getActionName(),
                'name' => $route->getName() ?: '-',
            ])
            ->values();

        if ($unreviewed->isEmpty()) {
            $this->info('Every authenticated tenant API endpoint has permission middleware or a documented controller-level authorization policy.');

            return self::SUCCESS;
        }

        $this->warn('The following authenticated endpoints rely on controller-level or self-service authorization and require explicit review:');
        $this->table(['Method', 'URI', 'Route name', 'Action'], $unreviewed->all());
        $this->line("{$unreviewed->count()} endpoint(s) require review.");

        return $this->option('strict') ? self::FAILURE : self::SUCCESS;
    }

    private function hasMiddleware(Route $route, array $needles): bool
    {
        return collect($route->gatherMiddleware())
            ->contains(fn (string $middleware): bool => collect($needles)
                ->contains(fn (string $needle): bool => str_contains($middleware, $needle)));
    }

    private function hasControllerAuthorization(Route $route): bool
    {
        return array_key_exists(
            $route->getActionName(),
            config('authorization.controller_authorized_actions', [])
        );
    }
}
