<?php

namespace App\Http\Controllers\Overtime;

use App\Http\Controllers\Controller;
use App\Http\Requests\OvertimeRequest;
use App\Repository\Overtime\OvertimeRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    private OvertimeRepositoryInterface $modelRepository;

    public function __construct(OvertimeRepositoryInterface $modelRepository)
    {
        $this->modelRepository = $modelRepository;
    }

    public function index(): JsonResponse
    {
        return $this->modelRepository->getList();
    }

    public function store(OvertimeRequest $request): JsonResponse
    {
        return $this->modelRepository->create($request->validated());
    }

    public function show(string $id): JsonResponse
    {
        return $this->modelRepository->find($id);
    }

    public function update(OvertimeRequest $request, string $id): JsonResponse
    {
        return $this->modelRepository->update($request->validated(), $id);
    }

    public function destroy(string $id): JsonResponse
    {
        return $this->modelRepository->delete($id);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);

        return $this->modelRepository->approve($id, $request->input('remarks'));
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);

        return $this->modelRepository->reject($id, $request->input('remarks'));
    }
}
