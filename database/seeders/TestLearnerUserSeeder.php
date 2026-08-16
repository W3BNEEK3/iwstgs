<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * A plain user with no RBAC role — deliberately not "learner" yet. The
 * 'learner' role is only ever granted by GrantLearnerRoleOnEnrolment when
 * this user actually submits the "Enrol as a Learner" form themselves
 * (see EnrolAsLearnerHandler / LearnerEnrolled), same as a real signup.
 * Unlike AdminUserSeeder's super_admin, this user logs in through the
 * normal /login route — /sys/login is reserved for super_admin.
 */
class TestLearnerUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insertOrIgnore([
            'id' => (string) Str::uuid(),
            'name' => 'Test Learner',
            'email' => 'learner@example.com',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
