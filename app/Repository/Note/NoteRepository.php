<?php

namespace App\Repository\Note;

use App\Models\Note;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\JsonResponse;

class NoteRepository extends BaseRepository implements NoteRepositoryInterface
{
    public function __construct(
        Note $model,
        ResponseServiceInterface $responseService,
        AuditLogServiceInterface $auditLogService,
    ) {
        parent::__construct($model, $responseService, $auditLogService);
    }

    public function getList(): JsonResponse
    {
        $archived = request()->boolean('archived');

        return $this->responseService->successResponse(
            $this->model->model_name,
            $this->model->filter()
                ->where('is_archived', $archived)
                ->orderByDesc('is_pinned')
                ->orderByDesc('updated_at')
                ->paginate(request()->integer('limit', 12))
        );
    }

}
