<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest as ModelRequest;
use App\Repository\Employee\EmployeeRepositoryInterface;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    private $modelRepository;

    public function __construct(EmployeeRepositoryInterface $modelRepository)
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

    public function generateEmployeeNo()
    {
        return $this->modelRepository->generateEmployeeNo();
    }

    public function getNumberSettings()
    {
        return $this->modelRepository->getEmployeeNumberSettings();
    }

    public function updateNumberSettings(Request $request)
    {
        $data = $request->validate([
            'strategy' => 'required|in:yearly_random,auto_increment,custom_format',
            'prefix' => 'required|string',
            'padding' => 'nullable|integer|min:1|max:10',
        ]);

        return $this->modelRepository->updateEmployeeNumberSettings($data);
    }

    public function reformatEmployeeNumbers()
    {
        return $this->modelRepository->reformatEmployeeNumbers();
    }
}
