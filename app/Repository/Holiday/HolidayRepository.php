<?php

namespace App\Repository\Holiday;

use App\Models\Holiday;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\JsonResponse;

class HolidayRepository extends BaseRepository implements HolidayRepositoryInterface
{
    public function __construct(
        Holiday $model,
        ResponseServiceInterface $responseService,
        AuditLogServiceInterface $auditLogService
    ) {
        parent::__construct($model, $responseService, $auditLogService);
    }

    public function getList(): JsonResponse
    {
        $sortByColumn = request()->input('sort_by_column', 'date');
        $sortBy = request()->input('sort_by', 'asc');
        $all = request()->boolean('all');
        $type = request()->string('type')->toString();
        $year = request()->integer('year');

        $allowedSortColumns = ['name', 'date', 'type', 'created_at', 'updated_at'];
        if (! in_array($sortByColumn, $allowedSortColumns, true)) {
            $sortByColumn = 'date';
        }

        $sortBy = strtolower($sortBy) === 'desc' ? 'desc' : 'asc';

        $query = $this->model->filter()->newQuery()
            ->when($year >= 1900 && $year <= 2200, fn ($query) => $query->whereYear('date', $year))
            ->when(in_array($type, Holiday::TYPES, true), fn ($query) => $query->where('type', $type))
            ->orderBy($sortByColumn, $sortBy);

        return $this->responseService->successResponse(
            $this->model->model_name,
            $all ? $query->get() : $query->paginate(request()->integer('limit', 10))
        );
    }
}
