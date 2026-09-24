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
            MedQueueScenario2Seeder::class,
            MedQueueScenario3Seeder::class,
            ShiftBoardSeeder::class,
            ShiftBoardScenario2Seeder::class,
            ShiftBoardScenario3Seeder::class,
            ExistingProjectsAdaptiveContentSeeder::class,
            PantryLinkSeeder::class,
            CampusBookSeeder::class,
            PayFlowSeeder::class,
            DiagnosticAssessmentSeeder::class,
        ]);
    }
}
