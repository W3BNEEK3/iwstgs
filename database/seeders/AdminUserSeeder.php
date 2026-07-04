<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $userId = (string) Str::uuid();

        // 1. Create the user
        DB::table('users')->insertOrIgnore([
            'id' => $userId,
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Attach the super_admin role (role_id = 1)
        DB::table('user_role')->insertOrIgnore([
            'user_id' => $userId,
            'role_id' => 1, // super_admin
        ]);
    }
}
