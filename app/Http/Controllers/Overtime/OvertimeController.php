<?php

namespace App\Http\Controllers\Overtime;

use App\Http\Controllers\Controller;
use App\Http\Requests\OvertimeRequest;
use App\Repository\Overtime\OvertimeRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    public function __construct(protected OvertimeRepositoryInterface $repository) {}

    /**
     * List overtime records. Supports the same query params as every other
     * BaseRepository-backed list endpoint: relations, sort_by_column,
     * sort_by, all, limit.
     */
    public function index(): JsonResponse
    {
        return $this->repository->getList();
    }

    /**
     * Create a new overtime request.
     */
    public function store(OvertimeRequest $request): JsonResponse
    {
        return $this->repository->create($request->validated());
    }

    /**
     * Show a single overtime record.
     */
    public function show(string $id): JsonResponse
    {
        return $this->repository->find($id);
    }

    /**
     * Update an overtime record.
     */
    public function update(OvertimeRequest $request, string $id): JsonResponse
    {
        return $this->repository->update($request->validated(), $id);
    }

    /**
     * Delete an overtime record.
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->repository->delete($id);
    }

    /**
     * Approve a pending overtime request.
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);

        return $this->repository->approve($id, $request->input('remarks'));
    }

    /**
     * Reject a pending overtime request.
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);

        return $this->repository->reject($id, $request->input('remarks'));
    }
}
