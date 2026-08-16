<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FeatureFlagSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
            TestLearnerUserSeeder::class,
            CompetenceDimensionSeeder::class,
            RoleDefinitionSeeder::class,
            MedQueueSeeder::class,
        ]);
    }
}
