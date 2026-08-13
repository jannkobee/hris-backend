<?php

namespace App\Http\Controllers\LeaveCredit;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveCreditRequest as ModelRequest;
use App\Repository\LeaveCredit\LeaveCreditRepositoryInterface;

class LeaveCreditController extends Controller
{
    private LeaveCreditRepositoryInterface $modelRepository;

    public function __construct(LeaveCreditRepositoryInterface $modelRepository)
    {
        $this->modelRepository = $modelRepository;
        $this->middleware('permission:view-leave-credits')->only(['index', 'show']);
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
