<?php

namespace App\Http\Controllers\LeaveCreditSetting;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveCreditSettingRequest as ModelRequest;
use App\Repository\LeaveCreditSetting\LeaveCreditSettingRepositoryInterface;

class LeaveCreditSettingController extends Controller
{
    private LeaveCreditSettingRepositoryInterface $modelRepository;

    public function __construct(LeaveCreditSettingRepositoryInterface $modelRepository)
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
