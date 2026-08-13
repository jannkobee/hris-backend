<?php

namespace App\Http\Controllers\AppSetting;

use App\Http\Controllers\Controller;
use App\Services\AppSettings\AppSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AppSettingController extends Controller
{
    public function __construct(private readonly AppSettingService $settings)
    {
        $this->middleware('permission:manage-app-settings')->only('update');
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'values' => $this->settings->all(),
                'definitions' => $this->settings->definitions(),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'values' => ['required', 'array'],
        ]);

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

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $values = $this->settings->update($payload['values']);

        return response()->json([
            'message' => 'App settings updated successfully.',
            'data' => [
                'values' => $values,
                'definitions' => $this->settings->definitions(),
            ],
        ], 202);
    }
}
