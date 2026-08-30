<?php

namespace App\Http\Controllers\Shift;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShiftTemplateRequest;
use App\Repository\ShiftTemplate\ShiftTemplateRepositoryInterface;

class ShiftTemplateController extends Controller
{
    private ShiftTemplateRepositoryInterface $repository;

    public function __construct(ShiftTemplateRepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->requireResourcePermissions('shifts');
    }

    public function index()
    {
        return $this->repository->getList();
    }

    public function store(ShiftTemplateRequest $request)
    {
        return $this->repository->create($request->validated());
    }

    public function show(string $shiftTemplate)
    {
        return $this->repository->find($shiftTemplate);
    }

    public function update(ShiftTemplateRequest $request, string $shiftTemplate)
    {
        return $this->repository->update($request->validated(), $shiftTemplate);
    }

    public function destroy(string $shiftTemplate)
    {
        return $this->repository->delete($shiftTemplate);
    }
}
