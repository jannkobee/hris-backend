<?php

namespace App\Http\Controllers\LeaveCredit;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveCreditForecastRequest;
use App\Http\Requests\LeaveCreditRequest as ModelRequest;
use App\Models\Employee;
use App\Repository\LeaveCredit\LeaveCreditRepositoryInterface;
use App\Services\LeaveAccrual\LeaveCreditForecastService;
use App\Services\Utils\ResponseServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class LeaveCreditController extends Controller
{
    private LeaveCreditRepositoryInterface $modelRepository;
    private ResponseServiceInterface $response;

    public function __construct(
        LeaveCreditRepositoryInterface $modelRepository,
        ResponseServiceInterface $response
    ) {
        $this->modelRepository = $modelRepository;
        $this->response = $response;
        $this->middleware('permission:view-leave-credits')->only(['index', 'show', 'forecast']);
    }

    public function forecast(LeaveCreditForecastRequest $request, LeaveCreditForecastService $forecastService): JsonResponse
    {
        $employee = Employee::query()->findOrFail($request->validated('employee_id'));
        $targetDate = Carbon::parse($request->validated('target_date'));
        $leaveTypeId = $request->validated('leave_type_id');

        $result = $forecastService->forecast($employee, $targetDate, $leaveTypeId);

        return $this->response->successResponse('Leave credit forecast', $result);
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
