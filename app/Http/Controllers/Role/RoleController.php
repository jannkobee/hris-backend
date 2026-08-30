<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest as ModelRequest;
use App\Models\Role;
use App\Repository\Role\RoleRepositoryInterface;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    private RoleRepositoryInterface $modelRepository;

    public function __construct(RoleRepositoryInterface $modelRepository)
    {
        $this->modelRepository = $modelRepository;
        $this->requireResourcePermissions('roles');
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
        $attributes = $request->validated();
        $role = Role::query()->findOrFail($id);

        if ($this->isProtectedSystemRole($role) && $attributes['name'] !== $role->name) {
            throw ValidationException::withMessages([
                'name' => 'The Admin and User system roles cannot be renamed.',
            ]);
        }

        return $this->modelRepository->update($attributes, $id);
    }

    public function destroy(string $id)
    {
        $role = Role::query()->findOrFail($id);

        if ($this->isProtectedSystemRole($role)) {
            throw ValidationException::withMessages([
                'role' => 'The Admin and User system roles cannot be deleted.',
            ]);
        }

        return $this->modelRepository->delete($id);
    }

    private function isProtectedSystemRole(Role $role): bool
    {
        return in_array($role->name, ['Admin', 'User'], true);
    }
}
