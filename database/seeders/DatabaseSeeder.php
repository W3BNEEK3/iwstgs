<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Phase 0
        $this->call(FeatureFlagSeeder::class);

        // Phase 1
        $this->call(RoleSeeder::class);

        // Phase 2
        $this->call(CompetenceDimensionSeeder::class);
        // role_definitions, concept_tags, project content: seeded via admin UI in Phase 4
    }
}
