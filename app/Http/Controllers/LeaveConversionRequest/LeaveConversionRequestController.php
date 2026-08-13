<?php

namespace App\Http\Controllers\LeaveConversionRequest;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveConversionRequestRequest as ModelRequest;
use App\Repository\LeaveConversionRequest\LeaveConversionRequestRepositoryInterface;
use Illuminate\Http\Request;

class LeaveConversionRequestController extends Controller
{
    private $modelRepository;

    public function __construct(LeaveConversionRequestRepositoryInterface $modelRepository)
    {
        $this->modelRepository = $modelRepository;
        $this->middleware('permission:view-leave-conversion-requests')->only(['index', 'show']);
        $this->middleware('permission:create-leave-conversion-requests')->only('store');
        $this->middleware('permission:manage-leave-conversion-requests')->only(['update', 'destroy']);
        $this->middleware('permission:approve-leave-conversion-requests')->only(['approve', 'reject']);
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

    public function approve(string $id)
    {
        return $this->modelRepository->approve($id);
    }

    public function reject(Request $request, string $id)
    {
        return $this->modelRepository->reject($id, $request->input('remarks'));
    }
}
