<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest as ModelRequest;
use App\Models\Role;
use App\Models\User;
use App\Repository\User\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    private UserRepositoryInterface $modelRepository;

    public function __construct(UserRepositoryInterface $modelRepository)
    {
        $this->modelRepository = $modelRepository;
        $this->requireResourcePermissions('users');
        $this->middleware('permission:manage-users')->only(['downloadTemplate', 'import']);
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
        $user = User::query()->findOrFail($id);

        $this->ensureAdministratorRemains($user, $attributes['role_id']);

        return $this->modelRepository->update($attributes, $id);
    }

    public function destroy(string $id)
    {
        $user = User::query()->findOrFail($id);

        $this->ensureAdministratorRemains($user);

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

    private function ensureAdministratorRemains(User $user, string $replacementRoleId = null): void
    {
        $adminRoleId = Role::query()->where('name', 'Admin')->value('id');

        if (! $adminRoleId
            || $user->role_id !== $adminRoleId
            || $replacementRoleId === $adminRoleId) {
            return;
        }

        $hasAnotherAdministrator = User::query()
            ->where('role_id', $adminRoleId)
            ->whereKeyNot($user->id)
            ->exists();

        if (! $hasAnotherAdministrator) {
            throw ValidationException::withMessages([
                'role_id' => 'Every organization must keep at least one administrator.',
            ]);
        }
    }
}
