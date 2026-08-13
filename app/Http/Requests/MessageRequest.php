<?php

namespace App\Http\Requests;

use App\Services\AppSettings\AppSettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settings = app(AppSettingService::class);
        $attachmentsEnabled = $settings->get('messaging.attachments_enabled', true);
        $maxSizeKb = (int) $settings->get('messaging.max_attachment_size_mb', 25) * 1024;

        return [
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachments'],
            'attachments' => [Rule::when(
                $attachmentsEnabled,
                ['nullable', 'array', 'max:5', 'required_without:body'],
                ['prohibited']
            )],
            'attachments.*' => [Rule::when(
                $attachmentsEnabled,
                [
                    'file',
                    "max:{$maxSizeKb}",
                    'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,7z,mp3,wav,m4a,mp4,mov,webm',
                ],
                ['prohibited']
            )],
        ];
    }
}
