<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'nurnasrullayev@gmail.com'],
            [
                'name' => "Nu'mon Nasrullayev",
                'password' => Hash::make('change_this_password_123'),
                'is_active' => true,
            ]
        );

        $user->assignRole('super_admin');
    }
}
