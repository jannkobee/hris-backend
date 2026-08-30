<?php

namespace Tests\Unit;

use App\Models\Announcement;
use App\Models\AppSetting;
use App\Models\Attendance;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeNumberSetting;
use App\Models\EmploymentStatus;
use App\Models\Holiday;
use App\Models\JobGrade;
use App\Models\LeaveConversionRequest;
use App\Models\LeaveCredit;
use App\Models\LeaveCreditSetting;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Logs\AuditLog;
use App\Models\Note;
use App\Models\Overtime;
use App\Models\PayrollPeriod;
use App\Models\Position;
use App\Models\Role;
use App\Models\ScheduledTask;
use App\Models\User;
use App\Models\WorkplaceMeeting;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Tests\TestCase;

class ModuleContractTest extends TestCase
{
    /**
     * @dataProvider moduleProvider
     *
     * @param  class-string<Model>|null  $model
     * @param  list<string>  $permissions
     */
    public function test_every_module_has_its_route_model_permissions_and_plan_contract(
        string $module,
        string $uriFragment,
        ?string $model,
        array $permissions,
        ?string $planFeature,
    ): void {
        $routes = collect(app('router')->getRoutes()->getRoutes());
        $this->assertTrue(
            $routes->contains(fn (Route $route): bool => str_contains($route->uri(), $uriFragment)),
            "{$module} has no registered API route containing '{$uriFragment}'."
        );

        if ($model !== null) {
            $this->assertTrue(class_exists($model), "{$module} model {$model} does not exist.");
            $this->assertContains(
                BelongsToOrganization::class,
                class_uses_recursive($model),
                "{$module} model must be tenant-scoped."
            );
            $this->assertContains(
                (new $model())->getTable(),
                config('tenancy.owned_tables'),
                "{$module} table is missing from the tenant schema inventory."
            );
        }

        $catalogSlugs = collect(config('permissions.catalog'))
            ->flatMap(fn (array $group): array => array_keys($group));
        foreach ($permissions as $permission) {
            $this->assertTrue(
                $catalogSlugs->contains($permission),
                "{$module} references unknown permission '{$permission}'."
            );
        }

        if ($planFeature !== null) {
            $this->assertArrayHasKey(
                $planFeature,
                config('plans.features'),
                "{$module} references unknown plan feature '{$planFeature}'."
            );
        }
    }

    public function test_every_controller_route_points_to_a_real_action(): void
    {
        foreach (app('router')->getRoutes()->getRoutes() as $route) {
            $action = $route->getActionName();
            if ($action === 'Closure' || ! str_contains($action, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $action, 2);
            $this->assertTrue(class_exists($controller), "Missing route controller {$controller}.");
            $this->assertTrue(method_exists($controller, $method), "Missing route action {$action}.");
        }
    }

    public function test_plan_definitions_only_reference_known_features(): void
    {
        $known = array_keys(config('plans.features'));

        foreach (config('plans.plans') as $code => $plan) {
            if ($plan['features'] === ['*']) {
                continue;
            }

            $this->assertSame(
                [],
                array_values(array_diff($plan['features'], $known)),
                "Plan {$code} references unknown features."
            );
        }
    }

    /** @return array<string, array{string, string, class-string<Model>|null, list<string>, string|null}> */
    public static function moduleProvider(): array
    {
        return [
            'authentication' => ['Authentication', '/auth', null, [], null],
            'dashboard' => ['Dashboard', '/dashboard', null, [], 'core_hr'],
            'profiles' => ['Profiles', '/profile', User::class, [], 'core_hr'],
            'users' => ['Users', '/users', User::class, ['view-users', 'manage-users'], 'core_hr'],
            'roles' => ['Roles', '/roles', Role::class, ['view-roles', 'manage-roles'], 'core_hr'],
            'role permissions' => ['Role permissions', '/role-permissions', Role::class, ['manage-role-permissions'], 'core_hr'],
            'employees' => ['Employees', '/employees', Employee::class, ['view-employees', 'manage-employees'], 'core_hr'],
            'employee number settings' => ['Employee number settings', '/employee-no', EmployeeNumberSetting::class, ['manage-employee-number-settings'], 'core_hr'],
            'employee documents' => ['Employee documents', '/employee-documents', EmployeeDocument::class, ['view-employee-documents', 'manage-employee-documents'], 'employee_documents'],
            'attendance' => ['Attendance', '/attendances', Attendance::class, ['view-attendances', 'manage-attendances'], 'attendance'],
            'workforce calendar' => ['Workforce calendar', '/holidays', Holiday::class, ['view-holidays', 'manage-holidays'], 'workforce_calendar'],
            'departments' => ['Departments', '/departments', Department::class, ['view-departments', 'manage-departments'], 'core_hr'],
            'positions' => ['Positions', '/positions', Position::class, ['view-positions', 'manage-positions'], 'core_hr'],
            'employment statuses' => ['Employment statuses', '/employment-statuses', EmploymentStatus::class, ['view-employment-statuses', 'manage-employment-statuses'], 'core_hr'],
            'job grades' => ['Job grades', '/job-grades', JobGrade::class, ['view-job-grades', 'manage-job-grades'], 'core_hr'],
            'leave types' => ['Leave types', '/leave-types', LeaveType::class, ['view-leave-types', 'manage-leave-types'], 'leave'],
            'leave requests' => ['Leave requests', '/leave-requests', LeaveRequest::class, ['view-leave-requests', 'create-leave-requests', 'manage-leave-requests', 'approve-leave-requests'], 'leave'],
            'leave credits' => ['Leave credits', '/leave-credits', LeaveCredit::class, ['view-leave-credits'], 'leave'],
            'leave credit settings' => ['Leave credit settings', '/leave-credit-settings', LeaveCreditSetting::class, ['view-leave-credit-settings', 'manage-leave-credit-settings'], 'leave'],
            'leave conversions' => ['Leave conversions', '/leave-conversion-requests', LeaveConversionRequest::class, ['view-leave-conversion-requests', 'create-leave-conversion-requests', 'manage-leave-conversion-requests', 'approve-leave-conversion-requests'], 'leave'],
            'overtime' => ['Overtime', '/overtime', Overtime::class, ['view-overtimes', 'create-overtimes', 'manage-overtimes', 'approve-overtimes'], 'overtime'],
            'announcements' => ['Announcements', '/announcements', Announcement::class, ['view-announcements', 'manage-announcements'], 'announcements'],
            'messaging' => ['Messaging', '/conversations', Conversation::class, [], 'messaging'],
            'notes' => ['Notes', '/notes', Note::class, ['view-notes', 'manage-notes'], 'notes'],
            'payroll' => ['Payroll', '/payroll-periods', PayrollPeriod::class, ['view-payroll', 'manage-payroll', 'approve-payroll', 'mark-payroll-paid'], 'payroll'],
            'workplace hub' => ['Workplace Hub', '/workplace-hub', WorkplaceMeeting::class, ['view-workplace-hub', 'create-meetings', 'manage-company-meetings', 'manage-meeting-rooms'], 'workplace_hub'],
            'scheduled tasks' => ['Scheduled tasks', '/scheduled-tasks', ScheduledTask::class, ['view-scheduled-tasks', 'manage-scheduled-tasks', 'run-scheduled-tasks'], 'automation'],
            'audit logs' => ['Audit logs', '/audit-logs', AuditLog::class, ['view-audit-logs'], 'audit_logs'],
            'app settings' => ['App settings', '/app-settings', AppSetting::class, ['manage-app-settings'], 'core_hr'],
            'navigation' => ['Navigation', '/navigation', null, [], 'core_hr'],
            'realtime' => ['Realtime', '/realtime', null, [], 'messaging'],
            'public APIs' => ['Public APIs', '/public-apis', null, [], null],
        ];
    }
}
