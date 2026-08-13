<?php

namespace App\Http\Requests;

use App\Services\AppSettings\AppSettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->routeIs('attendances.time-in', 'attendances.time-out')) {
            return $this->captureRules();
        }

        return [
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'time_in' => 'required|date_format:H:i',
            'time_in_notes' => 'nullable|string|max:255',
            'time_out' => 'nullable|date_format:H:i',
            'time_out_notes' => 'nullable|string|max:255',
            'ip_address' => 'nullable|ip',
        ];
    }

    private function captureRules(): array
    {
        $settings = app(AppSettingService::class);
        $photoEnabled = $settings->get('attendance.photo_capture_enabled', true);
        $locationEnabled = $settings->get('attendance.location_capture_enabled', true);
        $locationRequired = $locationEnabled && $settings->get('attendance.location_required', false);
        $notesEnabled = $settings->get('attendance.notes_enabled', true);
        $photoMaxKilobytes = (int) $settings->get('attendance.photo_max_size_mb', 5) * 1024;

        return [
            'notes' => [Rule::when($notesEnabled, ['nullable', 'string', 'max:500'], ['prohibited'])],
            'photo' => [Rule::when(
                $photoEnabled,
                ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', "max:{$photoMaxKilobytes}"],
                ['prohibited']
            )],
            'latitude' => [
                Rule::when($locationEnabled, [$locationRequired ? 'required' : 'nullable', 'numeric', 'between:-90,90'], ['prohibited']),
            ],
            'longitude' => [
                Rule::when($locationEnabled, [$locationRequired ? 'required' : 'nullable', 'numeric', 'between:-180,180'], ['prohibited']),
            ],
            'accuracy' => [
                Rule::when($locationEnabled, ['nullable', 'numeric', 'min:0'], ['prohibited']),
            ],
        ];
    }
}
