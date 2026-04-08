<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceRequest as ModelRequest;
use App\Repository\Attendance\AttendanceRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AttendanceController extends Controller
{
    private $modelRepository;

    public function __construct(AttendanceRepositoryInterface $modelRepository)
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

    public function timeIn(ModelRequest $request): JsonResponse
    {
        return $this->modelRepository->timeIn($request->user()->id, $request->validated());
    }

    public function timeOut(ModelRequest $request): JsonResponse
    {
        return $this->modelRepository->timeOut($request->user()->id, $request->validated());
    }

    public function today(Request $request): JsonResponse
    {
        return $this->modelRepository->getTodayAttendance($request->user()->id);
    }

    public function history(Request $request): JsonResponse
    {
        return $this->modelRepository->getUserHistory($request->user()->id, $request->all());
    }
}
