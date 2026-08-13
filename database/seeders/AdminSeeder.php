<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::where('name', 'Admin')->first();

        User::firstOrCreate(
            ['email' => 'admin@base.com'],
            [
                'role_id' => $role->id,
                'first_name' => 'Admin',
                'gender' => 'Male',
                'birthday' => Carbon::now()->format('Y-m-d'),
                'password' => Hash::make('secret'),
            ]
        );
    }
}
