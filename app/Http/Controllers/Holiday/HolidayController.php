<?php

namespace App\Http\Controllers\Holiday;

use App\Http\Controllers\Controller;
use App\Http\Requests\HolidayRequest as ModelRequest;
use App\Http\Requests\ImportHolidaysRequest;
use App\Repository\Holiday\HolidayRepositoryInterface;
use App\Services\Holidays\HolidayImportService;
use App\Services\Utils\ResponseServiceInterface;

class HolidayController extends Controller
{
    private HolidayRepositoryInterface $modelRepository;

    private HolidayImportService $holidayImport;

    private ResponseServiceInterface $responseService;

    public function __construct(
        HolidayRepositoryInterface $modelRepository,
        HolidayImportService $holidayImport,
        ResponseServiceInterface $responseService
    ) {
        $this->modelRepository = $modelRepository;
        $this->holidayImport = $holidayImport;
        $this->responseService = $responseService;
        $this->requireResourcePermissions('holidays');
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

    public function import(ImportHolidaysRequest $request)
    {
        $this->authorizeImport($request);
        $result = $this->holidayImport->import($request->integer('year'), $request->input('country_code'));

        return $this->responseService->storeResponse('Holidays', $result);
    }

    private function authorizeImport(ImportHolidaysRequest $request): void
    {
        abort_unless($request->user()?->hasPermission('manage-holidays'), 403);
    }
}
