<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $activeSlugs = [];

        foreach (config('permissions.catalog', []) as $group => $permissions) {
            foreach ($permissions as $slug => [$name, $description]) {
                $activeSlugs[] = $slug;

                Permission::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'model' => $group,
                        'name' => $name,
                        'description' => $description,
                    ]
                );
            }
        }

        Permission::query()->whereNotIn('slug', $activeSlugs)->delete();
    }
}
