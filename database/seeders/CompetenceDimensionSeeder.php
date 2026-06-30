<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompetenceDimensionSeeder extends Seeder
{
    public function run(): void
    {
        $dimensions = [
            [
                'id'                   => 'dim_001',
                'name'                 => 'Problem Analysis & Decomposition',
                'short_label'          => 'Analysis',
                'core_question'        => 'Did the learner correctly analyse and decompose the problem?',
                'observable_indicators' => json_encode([
                    'Identifies all relevant constraints and requirements',
                    'Separates the problem into independently solvable sub-problems',
                    'Recognises ambiguity and asks clarifying questions',
                    'Identifies data flows and dependencies',
                    'Spots hidden assumptions in the problem statement',
                ]),
                'sequence_order'       => 1,
            ],
            [
                'id'                   => 'dim_002',
                'name'                 => 'Design & Architecture',
                'short_label'          => 'Design',
                'core_question'        => 'Did the learner produce a coherent, maintainable design?',
                'observable_indicators' => json_encode([
                    'Chooses appropriate patterns and abstractions for the problem',
                    'Justifies architectural decisions with clear reasoning',
                    'Considers scalability and change over time',
                    'Designs appropriate interfaces between components',
                    'Avoids over-engineering and premature optimisation',
                ]),
                'sequence_order'       => 2,
            ],
            [
                'id'                   => 'dim_003',
                'name'                 => 'Implementation & Coding',
                'short_label'          => 'Implementation',
                'core_question'        => 'Did the learner produce correct, readable, and maintainable code?',
                'observable_indicators' => json_encode([
                    'Code is correct and handles edge cases',
                    'Follows language idioms and project conventions',
                    'Names are clear and self-documenting',
                    'Functions are focused and appropriately sized',
                    'No unnecessary complexity or duplication',
                ]),
                'sequence_order'       => 3,
            ],
            [
                'id'                   => 'dim_004',
                'name'                 => 'Testing & Quality Assurance',
                'short_label'          => 'Testing',
                'core_question'        => 'Did the learner verify correctness and consider failure modes?',
                'observable_indicators' => json_encode([
                    'Writes meaningful tests that prove the code works',
                    'Tests edge cases and boundary conditions',
                    'Considers what could go wrong in production',
                    'Uses appropriate testing levels (unit, integration)',
                    'Tests are readable and maintainable',
                ]),
                'sequence_order'       => 4,
            ],
            [
                'id'                   => 'dim_005',
                'name'                 => 'Debugging & Problem-Solving',
                'short_label'          => 'Debugging',
                'core_question'        => 'Did the learner diagnose and resolve problems systematically?',
                'observable_indicators' => json_encode([
                    'Isolates the fault rather than guessing',
                    'Forms and tests hypotheses methodically',
                    'Reads and interprets error messages correctly',
                    'Uses debugging tools effectively',
                    'Documents what was tried and what worked',
                ]),
                'sequence_order'       => 5,
            ],
            [
                'id'                   => 'dim_006',
                'name'                 => 'Communication & Documentation',
                'short_label'          => 'Communication',
                'core_question'        => 'Did the learner communicate their work clearly and completely?',
                'observable_indicators' => json_encode([
                    'Writes clear technical documentation',
                    'Explains decisions and trade-offs',
                    'Adapts communication style to the intended audience',
                    'Produces accurate commit messages and PR descriptions',
                    'Comments code at the right level of abstraction',
                ]),
                'sequence_order'       => 6,
            ],
        ];

        // insertOrIgnore: safe to run multiple times — skips rows that already exist.
        // This is better than truncate + insert because it protects any manual edits
        // made to a live database without overwriting them.
        DB::table('competence_dimensions')->insertOrIgnore($dimensions);
    }
}
