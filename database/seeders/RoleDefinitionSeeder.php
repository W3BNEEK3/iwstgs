<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Competency\Infrastructure\Persistence\Eloquent\Model\RoleDefinitionModel;

class RoleDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        RoleDefinitionModel::updateOrCreate(
            ['title' => 'Backend Engineer'],
            [
                'specialization_tags'  => ['backend', 'api', 'databases'],
                'min_years_experience' => 1,
                'dimension_weights'    => [
                    'dim_problem_analysis' => 0.15, 'dim_design' => 0.20, 'dim_implementation' => 0.30,
                    'dim_testing' => 0.15, 'dim_debugging' => 0.15, 'dim_communication' => 0.05,
                ],
                'dimension_thresholds' => [
                    'dim_problem_analysis' => 'proficient', 'dim_design' => 'proficient',
                    'dim_implementation' => 'proficient', 'dim_testing' => 'developing',
                    'dim_debugging' => 'developing', 'dim_communication' => 'developing',
                ],
                'is_lead_role' => false,
            ],
        );
    }
}
