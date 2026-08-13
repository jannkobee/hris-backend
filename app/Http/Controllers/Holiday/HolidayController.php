<?php

namespace App\Http\Controllers\Holiday;

use App\Http\Controllers\Controller;
use App\Http\Requests\HolidayRequest as ModelRequest;
use App\Repository\Holiday\HolidayRepositoryInterface;

class HolidayController extends Controller
{
    public function __construct(private HolidayRepositoryInterface $modelRepository)
    {
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
}
