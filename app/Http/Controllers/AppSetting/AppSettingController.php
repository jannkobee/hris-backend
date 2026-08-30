<?php

namespace App\Http\Controllers\AppSetting;

use App\Http\Controllers\Controller;
use App\Services\AppSettings\AppSettingService;
use App\Services\Plans\PlanEntitlementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AppSettingController extends Controller
{
    private AppSettingService $settings;

    private PlanEntitlementService $entitlements;

    public function __construct(
        AppSettingService $settings,
        PlanEntitlementService $entitlements
    ) {
        $this->settings = $settings;
        $this->entitlements = $entitlements;
        $this->middleware(
            'permission:manage-app-settings,manage-organization-settings,manage-attendance-settings,manage-feature-settings,manage-payroll-settings'
        )->only('update');
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'values' => $this->settingsVisibleToPlan($request, $this->settings->all()),
                'definitions' => $this->settingsVisibleToPlan($request, $this->settings->definitions()),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'values' => ['required', 'array'],
        ]);

        $this->authorizeSettingKeys($request, array_keys($payload['values']));

        $definitions = config('app_settings', []);
        $errors = [];

        foreach ($payload['values'] as $key => $value) {
            if (! array_key_exists($key, $definitions)) {
                $errors["values.{$key}"][] = 'This app setting is not supported.';

                continue;
            }

            $validator = Validator::make(
                ['value' => $value],
                ['value' => $definitions[$key]['rules']]
            );

            if ($validator->fails()) {
                $errors["values.{$key}"] = $validator->errors()->get('value');
            }
        }

        $effectiveValues = array_merge($this->settings->all(), $payload['values']);
        if (($effectiveValues['attendance.location_required'] ?? false)
            && ! ($effectiveValues['attendance.location_capture_enabled'] ?? false)) {
            $errors['values.attendance.location_required'][] = 'Location cannot be required while location capture is disabled.';
        }

        if (($effectiveValues['payroll.sss_max_msc'] ?? 0) < ($effectiveValues['payroll.sss_min_msc'] ?? 0)) {
            $errors['values.payroll.sss_max_msc'][] = 'The maximum SSS MSC must be at least the minimum MSC.';
        }

        if (($effectiveValues['payroll.philhealth_salary_ceiling'] ?? 0) < ($effectiveValues['payroll.philhealth_salary_floor'] ?? 0)) {
            $errors['values.payroll.philhealth_salary_ceiling'][] = 'The PhilHealth ceiling must be at least the income floor.';
        }

        $weekdays = $effectiveValues['payroll.work_weekdays'] ?? [];
        if (! is_array($weekdays) || collect($weekdays)->contains(fn ($day) => ! is_numeric($day) || (int) $day < 1 || (int) $day > 7)) {
            $errors['values.payroll.work_weekdays'][] = 'Scheduled weekdays must contain values from 1 (Monday) through 7 (Sunday).';
        }

        if (($effectiveValues['payroll.scheduled_end_time'] ?? '00:00') <= ($effectiveValues['payroll.scheduled_start_time'] ?? '00:00')) {
            $errors['values.payroll.scheduled_end_time'][] = 'The scheduled end time must be after the start time.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $values = $this->settings->update($payload['values']);

        return response()->json([
            'message' => 'App settings updated successfully.',
            'data' => [
                'values' => $this->settingsVisibleToPlan($request, $values),
                'definitions' => $this->settingsVisibleToPlan($request, $this->settings->definitions()),
            ],
        ], 202);
    }

    private function authorizeSettingKeys(Request $request, array $keys): void
    {
        $user = $request->user();

        $unavailable = collect($keys)
            ->filter(function (string $key) use ($user): bool {
                $feature = match (true) {
                    str_starts_with($key, 'payroll.') => 'payroll',
                    str_starts_with($key, 'employee_documents.') => 'employee_documents',
                    default => null,
                };

                return $feature !== null
                    && ! $this->entitlements->allows($user?->organization, $feature);
            })
            ->values();

        if ($unavailable->isNotEmpty()) {
            throw new AuthorizationException(
                'Your organization\'s subscription plan does not include: '.$unavailable->join(', ').'.'
            );
        }

        if ($user?->hasPermission('manage-app-settings')) {
            return;
        }

        $unauthorized = collect($keys)
            ->filter(fn (string $key): bool => ! $user?->hasPermission($this->settings->permissionFor($key)))
            ->values();

        if ($unauthorized->isNotEmpty()) {
            throw new AuthorizationException(
                'You do not have permission to update: '.$unauthorized->join(', ').'.'
            );
        }
    }

    private function settingsVisibleToPlan(Request $request, array $settings): array
    {
        $organization = $request->user()?->organization;

        return collect($settings)
            ->reject(function (mixed $_value, string $key) use ($organization): bool {
                $feature = match (true) {
                    str_starts_with($key, 'payroll.') => 'payroll',
                    str_starts_with($key, 'employee_documents.') => 'employee_documents',
                    default => null,
                };

                return $feature !== null && ! $this->entitlements->allows($organization, $feature);
            })
            ->all();
    }
}
