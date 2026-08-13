<?php

namespace App\Http\Controllers\LeaveType;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveTypeRequest as ModelRequest;
use App\Repository\LeaveType\LeaveTypeRepositoryInterface;

class LeaveTypeController extends Controller
{
    private LeaveTypeRepositoryInterface $modelRepository;

    public function __construct(LeaveTypeRepositoryInterface $modelRepository)
    {
        $this->modelRepository = $modelRepository;
        // Every employee needs the active leave types when filing a request.
        // Management screens remain protected by the write permission.
        $this->middleware('permission:manage-leave-types')->only(['store', 'update', 'destroy']);
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
