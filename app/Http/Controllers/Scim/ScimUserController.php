<?php

namespace App\Http\Controllers\Scim;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scim\PatchScimUserRequest;
use App\Http\Requests\Scim\StoreScimUserRequest;
use App\Http\Requests\Scim\UpdateScimUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ScimUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->orderBy('email');
        $filter = (string) $request->query('filter');

        if (preg_match('/^(userName|externalId)\\s+eq\\s+"([^"]+)"$/i', $filter, $matches)) {
            $query->where($matches[1] === 'userName' ? 'email' : 'scim_external_id', $matches[2]);
        }

        $users = $query->get();

        return response()->json(['Resources' => $users->map(fn (User $user) => $this->resource($user))->values(), 'totalResults' => $users->count(), 'startIndex' => 1, 'itemsPerPage' => $users->count()]);
    }

    public function show(string $userId)
    {
        $user = $this->user($userId);

        return response()->json($this->resource($user));
    }

    public function store(StoreScimUserRequest $request)
    {
        $data = $this->attributes($request->validated());
        $user = User::query()->firstOrNew(['scim_external_id' => $data['externalId']]);
        $user->fill($data['attributes'] + ['password' => Hash::make(str()->random(48))]);
        if (! $user->exists) {
            $user->role_id = Role::query()->where('name', 'User')->value('id') ?? Role::query()->value('id');
        }
        $user->save();

        return response()->json($this->resource($user), 201);
    }

    public function update(UpdateScimUserRequest $request, string $userId)
    {
        $user = $this->user($userId);
        $data = $this->attributes($request->validated());
        $user->update($data['attributes'] + ['scim_external_id' => $data['externalId']]);

        return response()->json($this->resource($user->fresh()));
    }

    public function patch(PatchScimUserRequest $request, string $userId)
    {
        $user = $this->user($userId);
        $attributes = [];

        foreach ($request->validated('Operations') as $operation) {
            if (strtolower($operation['op']) !== 'replace') {
                continue;
            }

            $path = strtolower((string) ($operation['path'] ?? ''));
            $attributes = match ($path) {
                'active' => $attributes + ['is_active' => (bool) $operation['value']],
                'username' => $attributes + ['email' => strtolower((string) $operation['value'])],
                'name.givenname' => $attributes + ['first_name' => (string) $operation['value']],
                'name.familyname' => $attributes + ['last_name' => (string) $operation['value']],
                default => $attributes,
            };
        }

        $user->update($attributes);

        return response()->json($this->resource($user->fresh()));
    }

    private function attributes(array $data): array
    {

        return ['externalId' => $data['externalId'], 'attributes' => ['email' => strtolower($data['userName']), 'first_name' => $data['name']['givenName'], 'last_name' => $data['name']['familyName'], 'is_active' => $data['active'] ?? true]];
    }

    private function user(string $userId): User
    {
        return User::query()->findOrFail($userId);
    }

    private function resource(User $user): array
    {
        return ['schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'], 'id' => $user->id, 'externalId' => $user->scim_external_id, 'userName' => $user->email, 'active' => $user->is_active, 'name' => ['givenName' => $user->first_name, 'familyName' => $user->last_name], 'meta' => ['resourceType' => 'User']];
    }
}
