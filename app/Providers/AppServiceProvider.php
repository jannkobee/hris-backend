<?php

namespace App\Providers;

use App\Models\LeaveCreditSetting;
use App\Observers\LeaveCreditSettingObserver;
use App\Repository\Permission\PermissionRepository;
use App\Repository\Permission\PermissionRepositoryInterface;
use App\Services\AuditLog\AuditLogService;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Auth\AuthService;
use App\Services\Auth\AuthServiceInterface;
use App\Services\EmployeeNumber\EmployeeNumberService;
use App\Services\EmployeeNumber\EmployeeNumberServiceInterface;
use App\Services\Permission\PermissionService;
use App\Services\Permission\PermissionServiceInterface;
use App\Services\Utils\ResponseService;
use App\Services\Utils\ResponseServiceInterface;
use App\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, fn (): TenantContext => new TenantContext);
        $this->app->scoped(AuthServiceInterface::class, AuthService::class);
        $this->app->singleton(AuditLogServiceInterface::class, AuditLogService::class);
        $this->app->singleton(ResponseServiceInterface::class, ResponseService::class);
        $this->app->singleton(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->singleton(PermissionServiceInterface::class, PermissionService::class);
        $this->app->singleton(EmployeeNumberServiceInterface::class, EmployeeNumberService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        LeaveCreditSetting::observe(LeaveCreditSettingObserver::class);
    }
}
