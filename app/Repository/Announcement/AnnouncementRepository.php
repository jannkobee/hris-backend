<?php

namespace App\Repository\Announcement;

use App\Models\Announcement;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Security\HtmlSanitizer;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\JsonResponse;

class AnnouncementRepository extends BaseRepository implements AnnouncementRepositoryInterface
{
    private HtmlSanitizer $htmlSanitizer;

    public function __construct(Announcement $model, ResponseServiceInterface $responseService, AuditLogServiceInterface $auditLogService, HtmlSanitizer $htmlSanitizer)
    {
        parent::__construct($model, $responseService, $auditLogService);
        $this->htmlSanitizer = $htmlSanitizer;
    }

    public function create(array $attributes): JsonResponse
    {
        $attributes['created_by'] = auth()->id();
        $attributes = $this->sanitizeContent($attributes);

        return parent::create($attributes);
    }

    public function update(array $attributes, string|int $id): JsonResponse
    {
        return parent::update($this->sanitizeContent($attributes), $id);
    }

    private function sanitizeContent(array $attributes): array
    {
        if (array_key_exists('content', $attributes)) {
            $attributes['content'] = $this->htmlSanitizer->sanitize((string) $attributes['content']);
        }

        return $attributes;
    }
}
