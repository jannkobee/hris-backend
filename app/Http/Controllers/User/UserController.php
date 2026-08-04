<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest as ModelRequest;
use App\Repository\User\UserRepositoryInterface;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private UserRepositoryInterface $modelRepository;

    public function __construct(UserRepositoryInterface $modelRepository)
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

    public function downloadTemplate()
    {
        return $this->modelRepository->downloadTemplate();
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        return $this->modelRepository->import($request->file('file'));
    }
}
