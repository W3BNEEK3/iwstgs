<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Competency\Infrastructure\Persistence\Eloquent\Model\RoleDefinitionModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\RubricCriterionModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\RubricSetModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskCacVariantModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskExpectedDeliverableModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;

/**
 * Implementation Plan §5.5 / Business Logic §4.2: the diagnostic scenario a
 * learner takes once, globally, before ever choosing a real project — never
 * shown in the /learn catalogue (is_published: false; StartDiagnosticHandler
 * loads it directly, bypassing the publish filter on purpose). Both tasks are
 * stack-agnostic (written_explanation only, no code) and trade-off-heavy per
 * §4.2's rule that surface-level implementers must be distinguishable from
 * reasoning-capable learners — CAC fixed at mid throughout, never runtime-set.
 */
class DiagnosticAssessmentSeeder extends Seeder
{
    public function run(): void
    {
        $role = RoleDefinitionModel::updateOrCreate(
            ['title' => 'Diagnostic Candidate'],
            [
                'specialization_tags'  => ['diagnostic'],
                'min_years_experience' => 0,
                'dimension_weights'    => [
                    'dim_problem_analysis' => 0.25, 'dim_design' => 0.25, 'dim_implementation' => 0.10,
                    'dim_testing' => 0.10, 'dim_debugging' => 0.20, 'dim_communication' => 0.10,
                ],
                'dimension_thresholds' => [
                    'dim_problem_analysis' => 'developing', 'dim_design' => 'developing',
                    'dim_implementation' => 'developing', 'dim_testing' => 'developing',
                    'dim_debugging' => 'developing', 'dim_communication' => 'developing',
                ],
                'is_lead_role' => false,
            ],
        );

        $project = ProjectTemplateModel::updateOrCreate(
            ['title' => 'Initial Skills Diagnostic'],
            [
                'tagline'                 => 'A short assessment that sets your starting rank.',
                'project_type'            => 'assessment',
                'business_domain'         => 'general',
                'business_context'        => 'A one-time, stack-agnostic assessment used to determine a learner\'s starting rank before real project work begins.',
                'stakeholders'            => [],
                'overarching_constraints' => [],
                'tech_context'            => ['stack' => []],
                'specialization_tags'     => ['diagnostic'],
                'coding_guidelines'       => null,
                'velocity_estimate'       => null,
                'difficulty_level'        => 'intermediate',
                'is_published'            => false,
                'is_active'               => true,
            ],
        );

        if ($project->rubric_set_id === null) {
            $rubricSet = RubricSetModel::create(['project_id' => $project->id, 'version' => '1.0']);
            $project->update(['rubric_set_id' => $rubricSet->id]);
        } else {
            $rubricSet = RubricSetModel::find($project->rubric_set_id);
        }

        $scenario = ScenarioTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'sequence_order' => 1],
            [
                'title'                  => 'Initial Skills Diagnostic',
                'narrative_context'      => "You've been asked to complete a short, standalone assessment before joining any live project team. There's no existing codebase, no prior conversation to catch up on — just two problems that ask how you think through unfamiliar, ambiguous work.",
                'situation_trigger'      => "Email from the Platform Team: \"Before we place you on a project, we'd like you to work through two scenarios below. There's no single correct answer — we're looking at how you reason through the trade-offs.\"",
                'situation_trigger_type' => 'email',
                'learner_role_label'     => 'Incoming Engineer',
                'default_autonomy_level' => 'mid',
                'is_diagnostic'          => true,
                'is_published'           => false,
                'is_active'              => true,
            ],
        );

        $designTask = TaskModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'sequence_order' => 1],
            [
                'title'                  => 'Notification system trade-offs',
                'task_brief'             => "A logistics client needs a notification system that covers two very different needs: urgent same-day delivery alerts that must reach a driver within minutes, and routine daily digest emails to warehouse managers. The client's infrastructure team is small and has no experience running message queues. Propose an approach, and explain the trade-offs behind your design decisions.",
                'domain'                 => 'system_design',
                'task_type'              => 'diagnostic_scenario',
                'role_tags'              => ['diagnostic'],
                'tools'                  => [],
                'prerequisite_concepts'  => ['asynchronous processing', 'system design trade-offs'],
                'is_cac_runtime_set'     => false,
                'fixed_complexity'       => 'mid',
                'fixed_autonomy'         => 'mid',
                'fixed_context_fidelity' => 'mid',
                'consequence_task_ids'   => [],
                'suggestion_task_ids'    => [],
                'is_architectural'       => true,
                'planning_layer_active'  => false,
                'code_execution_config'  => null,
                'model_response_summary' => 'Separates the two delivery needs rather than forcing one mechanism to serve both, names a concrete approach for each (e.g. a lightweight queue vs. a scheduled batch job), and explicitly justifies the choice against the client\'s limited infrastructure experience rather than assuming a sophisticated ops setup.',
                'time_limit_minutes'     => null,
                'is_published'           => true,
                'is_active'              => true,
            ],
        );

        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $designTask->id, 'type' => 'written_explanation'],
            ['label' => 'Design reasoning', 'description' => 'Your proposed approach and the trade-offs behind it.', 'is_required' => true, 'display_order' => 1],
        );

        foreach (['low', 'mid', 'high'] as $level) {
            TaskCacVariantModel::updateOrCreate(
                ['task_id' => $designTask->id, 'complexity_level' => $level],
                [
                    'scenario_text'        => "Notification system trade-offs, presented at {$level} complexity (diagnostic always runs at mid).",
                    'scaffolding_text_low' => 'Consider: do both notification types need the same delivery mechanism?',
                    'scaffolding_text_mid' => 'Consider what happens if one delivery path is slow or unavailable.',
                    'scaffolding_text_high' => 'No scaffolding.',
                    'context_text_low'     => 'Full context given.',
                    'context_text_mid'     => 'Partial context — infrastructure maturity implied, not stated outright.',
                    'context_text_high'    => 'Sparse context.',
                ],
            );
        }

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $designTask->id, 'task_dimension_label' => 'Design reasoning'],
            [
                'parent_dimension_id' => 'dim_design', 'complexity_level' => 'mid',
                'criterion_text'      => 'The learner proposes a coherent approach and justifies the trade-offs behind it, rather than only describing a mechanism.',
                'weight'              => '0.600', 'dimension_weight' => '0.500',
                'claude_detection_hint' => "Look for an explicit trade-off being weighed (e.g. latency vs. operational complexity, real-time vs. batch), not just a named technology. A submission that says \"I'd use a message queue\" with no justification is at most developing; one that explains WHY a queue fits the urgent path but a scheduled job fits the digest, and why that split matters for a small ops team, is proficient or above.",
                'distinguished_description' => 'Separates the two needs, proposes a fitting approach for each, and explicitly reasons about the trade-off in light of the client\'s limited ops maturity.',
                'proficient_description'    => 'Separates the two needs and proposes a reasonable approach for each, with some justification.',
                'developing_description'    => 'Proposes an approach but does not clearly separate the two needs or justify the choice.',
                'beginning_description'     => 'Proposes a single undifferentiated mechanism with no trade-off reasoning.',
                'is_architectural' => true, 'is_planning_layer' => false, 'reference_doc_anchor' => null,
            ],
        );

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $designTask->id, 'task_dimension_label' => 'Problem framing'],
            [
                'parent_dimension_id' => 'dim_problem_analysis', 'complexity_level' => 'mid',
                'criterion_text'      => 'The learner identifies the client\'s operational constraint (small, inexperienced infrastructure team) as a real factor in the design, not just a detail to acknowledge.',
                'weight'              => '0.400', 'dimension_weight' => '0.250',
                'claude_detection_hint' => 'Look for the constraint actually shaping a decision — e.g. avoiding a design that requires operating a complex queue cluster — not just a passing mention of the client\'s team size.',
                'distinguished_description' => 'The constraint visibly drives at least one concrete design decision, stated explicitly.',
                'proficient_description'    => 'The constraint is acknowledged and loosely connected to the design.',
                'developing_description'    => 'The constraint is mentioned but not connected to any decision.',
                'beginning_description'     => 'The constraint is not mentioned at all.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => null,
            ],
        );

        $debugTask = TaskModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'sequence_order' => 2],
            [
                'title'                  => 'Diagnosing an intermittent failure',
                'task_brief'             => "A colleague tells you: \"Our order-export job usually finishes fine, but about once a day it silently produces a file with missing rows — no error, no crash, just fewer rows than expected. It doesn't happen on the same day of the week or at the same time.\" Walk through how you would investigate this, in order, and what you'd expect each step to tell you.",
                'domain'                 => 'debugging',
                'task_type'              => 'diagnostic_scenario',
                'role_tags'              => ['diagnostic'],
                'tools'                  => [],
                'prerequisite_concepts'  => ['systematic debugging', 'hypothesis testing'],
                'is_cac_runtime_set'     => false,
                'fixed_complexity'       => 'mid',
                'fixed_autonomy'         => 'mid',
                'fixed_context_fidelity' => 'mid',
                'consequence_task_ids'   => [],
                'suggestion_task_ids'    => [],
                'is_architectural'       => false,
                'planning_layer_active'  => false,
                'code_execution_config'  => null,
                'model_response_summary' => 'Lays out an ordered investigation (e.g. check for concurrency/race conditions before assuming data corruption, narrow down using logs/timing before guessing at a fix) rather than jumping straight to a guessed root cause, and explains what each step would confirm or rule out.',
                'time_limit_minutes'     => null,
                'is_published'           => true,
                'is_active'              => true,
            ],
        );

        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $debugTask->id, 'type' => 'written_explanation'],
            ['label' => 'Investigation plan', 'description' => 'Your step-by-step diagnostic approach.', 'is_required' => true, 'display_order' => 1],
        );

        foreach (['low', 'mid', 'high'] as $level) {
            TaskCacVariantModel::updateOrCreate(
                ['task_id' => $debugTask->id, 'complexity_level' => $level],
                [
                    'scenario_text'        => "Intermittent failure diagnosis, presented at {$level} complexity (diagnostic always runs at mid).",
                    'scaffolding_text_low' => 'Consider: what does "intermittent" rule out immediately?',
                    'scaffolding_text_mid' => 'Consider what evidence would distinguish a timing issue from a data issue.',
                    'scaffolding_text_high' => 'No scaffolding.',
                    'context_text_low'     => 'Full context given.',
                    'context_text_mid'     => 'Partial context — no logs provided, must reason about what to look for.',
                    'context_text_high'    => 'Sparse context.',
                ],
            );
        }

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $debugTask->id, 'task_dimension_label' => 'Systematic investigation'],
            [
                'parent_dimension_id' => 'dim_debugging', 'complexity_level' => 'mid',
                'criterion_text'      => 'The learner proposes an ordered investigation that narrows down the cause using evidence, rather than guessing at a fix first.',
                'weight'              => '0.600', 'dimension_weight' => '0.500',
                'claude_detection_hint' => 'Look for a sequence with a clear rationale for the order (e.g. rule out concurrency/timing before assuming a code bug, because the symptom is intermittent). A submission that jumps straight to "the code probably has a bug in the export logic" with no investigation sequence is at most developing.',
                'distinguished_description' => 'Ordered investigation with explicit reasoning for why each step comes before the next, correctly prioritising timing/concurrency given the intermittent symptom.',
                'proficient_description'    => 'Ordered investigation that would plausibly narrow down the cause.',
                'developing_description'    => 'Some investigation steps named but no clear order or rationale.',
                'beginning_description'     => 'Jumps directly to a guessed fix with no investigation.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => null,
            ],
        );

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $debugTask->id, 'task_dimension_label' => 'Communication clarity'],
            [
                'parent_dimension_id' => 'dim_communication', 'complexity_level' => 'mid',
                'criterion_text'      => 'Each investigation step states what outcome would confirm or rule out a hypothesis, not just what action to take.',
                'weight'              => '0.400', 'dimension_weight' => '0.250',
                'claude_detection_hint' => 'Look for language like "if X, then Y is the cause; if not, move to..." rather than a bare list of actions with no stated expected outcome.',
                'distinguished_description' => 'Every step names both the action and what result would confirm/rule it out.',
                'proficient_description'    => 'Most steps name an expected outcome.',
                'developing_description'    => 'Steps are listed but expected outcomes are vague or missing.',
                'beginning_description'     => 'No expected outcomes stated anywhere.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => null,
            ],
        );
    }
}
