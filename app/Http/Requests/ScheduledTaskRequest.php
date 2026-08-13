<?php

namespace App\Http\Requests;

use Cron\CronExpression;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;

class ScheduledTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Route-model binding: scheduled_task
        $task = $this->route('scheduled_task');
        $taskId = is_object($task) ? $task->id : $task;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('scheduled_tasks', 'name')->ignore($taskId),
            ],
            'description' => ['nullable', 'string'],
            'command' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $command = strtok(trim((string) $value), ' ');
                    if (! $command || ! array_key_exists($command, Artisan::all())) {
                        $fail('The command must be a registered Artisan command.');
                    }
                },
            ],
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'yearly', 'custom'])],

            'run_time' => [
                Rule::requiredIf(in_array($this->frequency, ['daily', 'weekly', 'monthly', 'yearly'])),
                'nullable',
                'date_format:H:i',
            ],

            'run_days' => [Rule::requiredIf($this->frequency === 'weekly'), 'nullable', 'array'],
            'run_days.*' => ['integer', 'between:0,6'],

            'run_day_of_month' => [
                Rule::requiredIf(in_array($this->frequency, ['monthly', 'yearly'])),
                'nullable',
                'integer',
                'between:1,31',
            ],

            'run_months' => [Rule::requiredIf($this->frequency === 'yearly'), 'nullable', 'array'],
            'run_months.*' => ['integer', 'between:1,12'],

            'cron_expression' => [
                Rule::requiredIf($this->frequency === 'custom'),
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value && ! CronExpression::isValidExpression($value)) {
                        $fail('The cron expression is invalid.');
                    }
                },
            ],

            'timezone' => ['nullable', 'timezone'],

            'is_active' => ['boolean'],
        ];
    }
}
