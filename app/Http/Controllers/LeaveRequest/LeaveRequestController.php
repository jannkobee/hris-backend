<?php

namespace App\Http\Controllers\LeaveRequest;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveRequestRequest as ModelRequest;
use App\Repository\LeaveRequest\LeaveRequestRepositoryInterface;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    private LeaveRequestRepositoryInterface $modelRepository;

    public function __construct(LeaveRequestRepositoryInterface $modelRepository)
    {
        $this->modelRepository = $modelRepository;
        $this->middleware('permission:view-leave-requests')->only(['index', 'show', 'downloadAttachment']);
        $this->middleware('permission:create-leave-requests')->only('store');
        $this->middleware('permission:manage-leave-requests')->only('update');
        $this->middleware('permission:approve-leave-requests')->only(['approve', 'reject']);
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
        $data = $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);

        return $this->modelRepository->reject($id, $data['remarks'] ?? null);
    }

    public function cancel(Request $request, string $id)
    {
        $data = $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);

        return $this->modelRepository->cancel($id, $data['remarks'] ?? null);
    }

    public function downloadAttachment(string $id, string $attachment)
    {
        return $this->modelRepository->downloadAttachment($id, $attachment);
    }
}
