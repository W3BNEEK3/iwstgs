<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Competency\Infrastructure\Persistence\Eloquent\Model\CompetenceDimensionModel;

class CompetenceDimensionSeeder extends Seeder
{
    public function run(): void
    {
        $dimensions = [
            ['id' => 'dim_problem_analysis', 'name' => 'Problem Analysis & Decomposition', 'short_label' => 'Problem Analysis',
             'core_question' => 'Does the learner understand the problem before solving it?',
             'observable_indicators' => ['Requirements identification','Constraint acknowledgment','Sub-problem breakdown','Ambiguity navigation'],
             'sequence_order' => 1],
            ['id' => 'dim_design', 'name' => 'Design & Architecture', 'short_label' => 'Design & Architecture',
             'core_question' => 'Does the learner make justifiable structural decisions?',
             'observable_indicators' => ['Separation of concerns','Pattern selection','Scalability consideration','Trade-off articulation'],
             'sequence_order' => 2],
            ['id' => 'dim_implementation', 'name' => 'Implementation & Coding', 'short_label' => 'Implementation & Coding',
             'core_question' => 'Does the learner translate design into working, quality code?',
             'observable_indicators' => ['Correctness','Code structure','Readability','Error handling','Adherence to standards'],
             'sequence_order' => 3],
            ['id' => 'dim_testing', 'name' => 'Testing & Quality Assurance', 'short_label' => 'Testing & QA',
             'core_question' => 'Does the learner verify and validate their work?',
             'observable_indicators' => ['Test coverage','Test strategy','Edge case identification','Defect reporting'],
             'sequence_order' => 4],
            ['id' => 'dim_debugging', 'name' => 'Debugging & Problem-Solving', 'short_label' => 'Debugging & Problem-Solving',
             'core_question' => 'Does the learner identify and resolve failures methodically?',
             'observable_indicators' => ['Root cause isolation','Hypothesis testing','Log interpretation','Fix verification'],
             'sequence_order' => 5],
            ['id' => 'dim_communication', 'name' => 'Communication & Documentation', 'short_label' => 'Communication & Docs',
             'core_question' => 'Does the learner communicate their work clearly and professionally?',
             'observable_indicators' => ['Code comments','Technical documentation','Decision justification','Clarity of explanation'],
             'sequence_order' => 6],
        ];

        foreach ($dimensions as $d) {
            CompetenceDimensionModel::updateOrCreate(['id' => $d['id']], $d);
        }
    }
}
