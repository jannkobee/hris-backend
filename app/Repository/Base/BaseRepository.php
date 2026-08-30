<?php

namespace App\Repository\Base;

use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use PDOException;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    protected ResponseServiceInterface $responseService;

    protected AuditLogServiceInterface $auditLogService;

    public function __construct(Model $model, ResponseServiceInterface $responseService, AuditLogServiceInterface $auditLogService)
    {
        $this->model = $model;
        $this->responseService = $responseService;
        $this->auditLogService = $auditLogService;
    }

    public function getList(): JsonResponse
    {
        $relations = request()->input('relations');
        $sortByColumn = request()->input('sort_by_column', 'created_at');
        $sortBy = request()->input('sort_by', 'desc');
        $all = request()->boolean('all');

        $modelName = $this->model->model_name;

        $query = $this->applyVisibilityScope(
            $this->model->filter()->newQuery()
        )->orderBy($sortByColumn, $sortBy);

        $query->when($relations, function (Builder $query) use ($relations) {
            $relations = explode(',', $relations);

            $query->with($relations);
        });

        return $this->responseService->successResponse(
            $modelName,
            $all ?
                $query->get() :
                $query->paginate(request()->input('limit') ?? 10)
        );
    }

    protected function applyVisibilityScope(Builder $query): Builder
    {
        return $query;
    }

    public function create(array $attributes): JsonResponse
    {
        $relations = request()->input('relations');

        $created = $this->model->create($attributes)->fresh();

        $this->auditLogService->insertLog($created, 'create', [
            'record_id' => $created->getKey(),
            'after' => $created->toArray(),
        ]);

        if ($relations) {
            $relations = explode(',', $relations);

            $created->load($relations);
        }

        return $this->responseService->storeResponse(
            $this->model->model_name,
            $created
        );
    }

    public function find(string $id): JsonResponse
    {
        $relations = request()->query('relations');
        $record = $this->model->find($id);

        if ($record) {
            if ($relations) {
                $record->load(explode(',', $relations));
            }

            return $this->responseService->successResponse(
                $this->model->model_name,
                $record
            );
        }

        throw ValidationException::withMessages([
            'record_not_found' => 'Record not found',
        ]);
    }

    public function update(array $attributes, string|int $id): JsonResponse
    {
        $relations = request()->input('relations');

        $data = $this->model->find($id);

        if ($data) {
            $before = $data->toArray();
            $data->update($attributes);
            $data->refresh();
            $this->auditLogService->insertLog($data, 'update', [
                'record_id' => $data->getKey(),
                'before' => $before,
                'after' => $data->toArray(),
            ]);

            if ($relations) {
                $relations = explode(',', $relations);

                $data->load($relations);
            }

            return $this->responseService->updateResponse(
                $this->model->model_name,
                $data
            );
        }
        throw ValidationException::withMessages([
            'record_not_found' => 'Record not found',
        ]);
    }

    public function delete(string $id): JsonResponse
    {
        $data = $this->model->find($id);

        if ($data) {
            try {
                $snapshot = $data->toArray();
                $data->forceDelete();

                $this->auditLogService->insertLog($this->model, 'delete', [
                    'record_id' => $id,
                    'before' => $snapshot,
                ]);

                return $this->responseService->deleteResponse(
                    $this->model->model_name,
                    true
                );
            } catch (QueryException $e) {
                logger()->error('Error deleting record.'.$e->getMessage());

                if ($e->getPrevious() instanceof PDOException && $e->errorInfo) {
                    if ($e->errorInfo[0] === '23000' && $e->errorInfo[1] === 1451) {
                        throw ValidationException::withMessages([
                            'invalid_delete' => 'Cannot delete. Record is referenced to other table.',
                        ]);
                    }
                }

                throw ValidationException::withMessages([
                    'error' => 'Something went wrong deleting a record.',
                ]);
            } catch (Exception $e) {
                logger()->error('Error deleting record.'.$e->getMessage());

                throw ValidationException::withMessages([
                    'error' => 'Something went wrong deleting a record.',
                ]);
            }
        }

        throw ValidationException::withMessages([
            'record_not_found' => 'Record not found',
        ]);
    }

    public function downloadTemplate()
    {
        $modelClass = get_class($this->model);
        $filename = Str::snake(class_basename($modelClass)).'_template.xlsx';

        return Excel::download($modelClass::importTemplate(), $filename);
    }

    public function import(UploadedFile $file): JsonResponse
    {
        $modelClass = get_class($this->model);
        $import = $modelClass::importer($this);

        Excel::import($import, $file);

        if ($import->failures()->isNotEmpty() || $import->errors()->isNotEmpty()) {
            return response()->json([
                'message' => "Imported {$import->createdCount()} record(s), some rows were skipped.",
                'failures' => $import->failures(),
                'errors' => $import->errors(),
            ], 422);
        }

        return response()->json([
            'message' => "Imported {$import->createdCount()} record(s) successfully.",
        ]);
    }
}
