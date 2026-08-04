<?php

namespace App\Http\Controllers\LeaveRequest;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveRequestRequest as ModelRequest;
use App\Repository\LeaveRequest\LeaveRequestRepositoryInterface;

class LeaveRequestController extends Controller
{
    private LeaveRequestRepositoryInterface $modelRepository;

    public function __construct(LeaveRequestRepositoryInterface $modelRepository)
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
