<?php

namespace App\Http\Controllers\EmploymentStatus;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmploymentStatusRequest as ModelRequest;
use App\Repository\EmploymentStatus\EmploymentStatusRepositoryInterface;

class EmploymentStatusController extends Controller
{
    private $modelRepository;

    public function __construct(EmploymentStatusRepositoryInterface $modelRepository)
    {
        $this->modelRepository = $modelRepository;
    }

    public function index()
    {
        return $this->modelRepository->getList();
    }

    public function store(ModelRequest $request)
    {
        return $this->modelRepository->create($request->validated());
    }

    public function show(string $id)
    {
        return $this->modelRepository->find($id);
    }

    public function update(ModelRequest $request, string $id)
    {
        return $this->modelRepository->update($request->validated(), $id);
    }

    public function destroy(string $id)
    {
        return $this->modelRepository->delete($id);
    }
}
