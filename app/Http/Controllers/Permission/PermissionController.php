<?php

namespace App\Http\Controllers\Permission;

use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionRequest as ModelRequest;
use App\Models\Permission;
use App\Repository\Permission\PermissionRepositoryInterface;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    private PermissionRepositoryInterface $modelRepository;

    public function __construct(PermissionRepositoryInterface $modelRepository)
    {
        $this->modelRepository = $modelRepository;
    }

    public function index()
    {
        return $this->modelRepository->getList();
    }

    public function presets(): JsonResponse
    {
        $templates = config('permissions.role_templates', []);
        $configuredSlugs = collect($templates)
            ->flatMap(static fn (array $template): array => $template['permission_slugs'] ?? [])
            ->filter(static fn (mixed $slug): bool => is_string($slug) && $slug !== '')
            ->unique()
            ->values();

        $permissionsBySlug = Permission::query()
            ->whereIn('slug', $configuredSlugs)
            ->get(['id', 'slug'])
            ->keyBy('slug');

        $presets = collect($templates)->map(function (array $template, string $key) use ($permissionsBySlug): array {
            $configuredTemplateSlugs = collect($template['permission_slugs'] ?? [])
                ->filter(static fn (mixed $slug): bool => is_string($slug) && $slug !== '')
                ->unique()
                ->values();
            $resolvedPermissions = $configuredTemplateSlugs
                ->map(static fn (string $slug) => $permissionsBySlug->get($slug))
                ->filter()
                ->values();

            return [
                'key' => $key,
                'name' => $template['name'] ?? $key,
                'description' => $template['description'] ?? '',
                'icon' => $template['icon'] ?? null,
                'color' => $template['color'] ?? null,
                'permission_ids' => $resolvedPermissions->pluck('id')->all(),
                'permission_slugs' => $resolvedPermissions->pluck('slug')->all(),
                'missing_permission_slugs' => $configuredTemplateSlugs
                    ->reject(static fn (string $slug): bool => $permissionsBySlug->has($slug))
                    ->values()
                    ->all(),
            ];
        })->values();

        return response()->json(['data' => $presets]);
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
