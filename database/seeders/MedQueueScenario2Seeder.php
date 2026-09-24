<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioReferenceMaterialModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskExpectedDeliverableModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskCacVariantModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskGuidancePromptModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\RubricCriterionModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\BacklogItemTemplateModel;

/**
 * MedQueue Scenario 2 — Performance & Resilience.
 *
 * Content Rationale: Sprint 1 established the core queue API. Now the
 * clinic goes to a second site. Concurrent enrollments spike and the
 * queue endpoint starts timing out under load. Learners must diagnose a
 * slow query, add appropriate indices/caching, and articulate the trade-
 * offs. This targets dim_design and dim_implementation under high CAC
 * complexity settings, while dim_problem_analysis is also in scope.
 *
 * CAC Differentiation (Gap 5 fix):
 *   low  — "Step-by-step: describe the problem before writing any fix."
 *   mid  — "The slow query has been identified for you; decide the fix."
 *   high — "Diagnose and fix; no hints given."
 */
class MedQueueScenario2Seeder extends Seeder
{
    public function run(): void
    {
        $project = ProjectTemplateModel::where('title', 'MedQueue — Hospital Patient Queue System')->firstOrFail();
        $rubricSet = $project->rubricSet;

        // ── Scenario ──────────────────────────────────────────────────────────
        $scenario = ScenarioTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'sequence_order' => 2],
            [
                'title'                  => 'Sprint 2 — Performance Under Load',
                'narrative_context'      => 'The clinic has expanded to a second site. Patient volume doubled overnight and the queue system is now slow: the position-lookup endpoint is taking 4–6 seconds under concurrent load, and two nurses have already complained. The Tech Lead has asked you to investigate and fix it before tomorrow morning.',
                'situation_trigger'      => "Email from Tech Lead: \"Position-lookup is timing out for about 1 in 5 nurses. I've pulled the slow-query log — the attached file has the worst offenders. Can you look at this today?\"",
                'situation_trigger_type' => 'email',
                'learner_role_label'     => 'Backend Engineer',
                'default_autonomy_level' => 'mid',
                'is_diagnostic'          => false,
                'is_published'           => true,
                'is_active'              => true,
            ],
        );

        ScenarioReferenceMaterialModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'title' => 'Slow query log (attached)'],
            [
                'material_type'    => 'report',
                'content'          => "Query: SELECT * FROM queue_entries WHERE is_served = 0 ORDER BY enqueue_time ASC\nDuration: avg 4230ms at 40 concurrent connections\nRows examined: 18,500 (full table scan)\nExplain plan: type=ALL, key=NULL, rows=18500",
                'embedded_signals' => ['no index on is_served', 'no composite index on (is_served, enqueue_time)', 'SELECT * returns unused columns'],
                'display_order'    => 1,
            ],
        );

        ScenarioReferenceMaterialModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'title' => 'MQ-31: Diagnose and fix position-lookup performance'],
            [
                'material_type'    => 'ticket',
                'content'          => "Performance issue: GET /queue/{id}/position times out under load.\nAcceptance criteria:\n- p95 response time < 500ms under 40 concurrent requests\n- Fix must not change the API contract (same request/response shape)\n- Explain the root cause and your chosen fix in the PR description",
                'embedded_signals' => ['must not change API contract', 'explain the root cause — just submitting a migration is not enough'],
                'display_order'    => 2,
            ],
        );

        // ── Task 1: Diagnose and fix the slow query ────────────────────────────
        $diagTask = TaskModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'sequence_order' => 1],
            [
                'title'      => 'Diagnose and fix the position-lookup performance issue',
                'task_brief' => "The position-lookup endpoint is slow. Using only the slow-query log provided, diagnose the root cause and fix it. Your deliverable must include: (1) a written explanation of what the root cause is and why, (2) the fix itself (migration, query change, or caching strategy), and (3) a note on any trade-offs your chosen fix introduces.",
                'domain'     => 'backend', 'task_type' => 'core',
                'role_tags'  => ['backend'], 'tools' => ['php', 'laravel', 'mysql'],
                'prerequisite_concepts' => ['database indexing', 'query optimization', 'explain plans'],
                'is_cac_runtime_set'    => true,
                'fixed_complexity'      => null, 'fixed_autonomy' => null, 'fixed_context_fidelity' => null,
                'is_architectural'      => false, 'planning_layer_active' => true,
                'code_execution_config' => ['language' => 'php'],
                'model_response_summary' => 'Identifies the full-table scan as the root cause (no index on is_served/enqueue_time), adds a composite index via migration, optionally notes the trade-off of index write overhead, and confirms no API contract change.',
                'time_limit_minutes'    => 90, 'is_published' => true, 'is_active' => true,
            ],
        );

        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $diagTask->id, 'type' => 'written_explanation'],
            ['label' => 'Root cause analysis', 'description' => 'What is slow and why — reference the slow-query log.', 'is_required' => true, 'display_order' => 1],
        );
        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $diagTask->id, 'type' => 'code'],
            ['label' => 'Fix (migration / query change)', 'description' => 'The actual fix applied.', 'is_required' => true, 'display_order' => 2],
        );
        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $diagTask->id, 'type' => 'written_explanation'],
            ['label' => 'Trade-off note', 'description' => 'Any side-effects or trade-offs your fix introduces.', 'is_required' => false, 'display_order' => 3],
        );

        // Differentiated CAC variants — real scenario text at each level
        TaskCacVariantModel::updateOrCreate(
            ['task_id' => $diagTask->id, 'complexity_level' => 'low'],
            [
                'scenario_text'        => "The slow-query log shows a full-table scan (key=NULL) on queue_entries. Your job: explain what a full-table scan is, why it's slow here, and add a database index that fixes it. You don't need to worry about trade-offs at this stage.",
                'scaffolding_text_low' => "Start with the EXPLAIN plan line in the log: 'type=ALL, key=NULL'. What does that mean?",
                'scaffolding_text_mid' => "The EXPLAIN plan is attached. What does 'type=ALL' indicate, and what's the fix?",
                'scaffolding_text_high'=> "No scaffolding.",
                'context_text_low'     => "The full slow-query log is provided. The EXPLAIN plan is included and annotated.",
                'context_text_mid'     => "The slow-query log is provided. The EXPLAIN plan is included.",
                'context_text_high'    => "You have the raw log. No annotations.",
            ],
        );

        TaskCacVariantModel::updateOrCreate(
            ['task_id' => $diagTask->id, 'complexity_level' => 'mid'],
            [
                'scenario_text'        => "The slow-query log confirms a full-table scan on queue_entries, likely due to missing indices on the columns used in the WHERE clause and ORDER BY. Your job: choose between adding a composite index and adding a caching layer, implement your choice, and explain the trade-off you considered before deciding.",
                'scaffolding_text_low' => "Consider: what are the columns in the WHERE clause? Which direction does the ORDER BY sort?",
                'scaffolding_text_mid' => "Think about write overhead when adding an index vs cache invalidation when using a cache.",
                'scaffolding_text_high'=> "No scaffolding.",
                'context_text_low'     => "The full slow-query log is provided including EXPLAIN output.",
                'context_text_mid'     => "The slow-query log is provided.",
                'context_text_high'    => "You have the raw slow-query log.",
            ],
        );

        TaskCacVariantModel::updateOrCreate(
            ['task_id' => $diagTask->id, 'complexity_level' => 'high'],
            [
                'scenario_text'        => "You have the slow-query log. Diagnose the root cause, decide on the appropriate fix (index, caching, query rewrite, or a combination), implement it, and document your reasoning — including why you rejected the alternatives. The clinic also needs assurance that your fix does not break the existing API contract.",
                'scaffolding_text_low' => "Start with the EXPLAIN plan. What type of scan is occurring?",
                'scaffolding_text_mid' => "What options do you have, and what are the trade-offs of each?",
                'scaffolding_text_high'=> "No scaffolding.",
                'context_text_low'     => "The full slow-query log is provided with EXPLAIN output and row counts.",
                'context_text_mid'     => "The slow-query log is provided.",
                'context_text_high'    => "Raw slow-query log only.",
            ],
        );

        TaskGuidancePromptModel::updateOrCreate(
            ['task_id' => $diagTask->id, 'trigger_dimension' => 'dim_problem_analysis'],
            [
                'prompt_text'           => 'What is the difference between a full-table scan and an index scan? Which one is happening here?',
                'autonomy_level_filter' => 'low', 'delivery_mode' => 'proactive', 'display_order' => 1,
            ],
        );

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $diagTask->id, 'task_dimension_label' => 'Root cause identification'],
            [
                'parent_dimension_id'    => 'dim_problem_analysis',
                'complexity_level'       => 'mid',
                'criterion_text'         => 'The learner correctly identifies the full-table scan as the root cause and links it to the missing index on the WHERE/ORDER BY columns — not just "the query is slow".',
                'weight'                 => '0.400', 'dimension_weight' => '0.300',
                'claude_detection_hint'  => 'Look for explicit mention of the index gap (is_served column, enqueue_time column, or the composite). A diagnosis of "slow query" without identifying why (no index) is developing at best.',
                'distinguished_description' => 'Precise diagnosis referencing the EXPLAIN plan columns, names both the missing columns, and explains why a composite is better than two separate indices.',
                'proficient_description'    => 'Correctly identifies the missing index on the relevant columns.',
                'developing_description'    => 'Identifies the query as slow but gives a vague diagnosis (e.g. "the table is too big").',
                'beginning_description'     => 'No meaningful root-cause analysis.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => 'Slow query log (Sprint 2)',
            ],
        );

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $diagTask->id, 'task_dimension_label' => 'Fix quality and trade-off reasoning'],
            [
                'parent_dimension_id'    => 'dim_design',
                'complexity_level'       => 'mid',
                'criterion_text'         => 'The fix is appropriate for the root cause, does not break the API contract, and the learner acknowledges at least one trade-off.',
                'weight'                 => '0.600', 'dimension_weight' => '0.400',
                'claude_detection_hint'  => 'A bare migration with no trade-off reasoning is proficient. Distinguished requires explicit reasoning about write overhead, index cardinality, or why an alternative (e.g. caching) was or was not chosen.',
                'distinguished_description' => 'Correct fix, no API contract break, clear trade-off reasoning with an explicit alternative considered and rejected.',
                'proficient_description'    => 'Correct fix, no API contract break, at least one trade-off noted.',
                'developing_description'    => 'Fix is in the right direction but incomplete or slightly breaks the contract.',
                'beginning_description'     => 'No meaningful fix or the fix makes things worse.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => null,
            ],
        );

        // ── Task 2: Add a health-check endpoint ───────────────────────────────
        $healthTask = TaskModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'sequence_order' => 2],
            [
                'title'      => 'Add a health-check endpoint for operations monitoring',
                'task_brief' => "The IT team at the second site needs to know if the queue system is reachable before nurse shift start. Build GET /health that returns a 200 with a small JSON body when the system is up, and a 503 when it can't reach the database. Explain what you chose to check and why you drew the line there (i.e. what you deliberately didn't include).",
                'domain'     => 'backend', 'task_type' => 'core',
                'role_tags'  => ['backend'], 'tools' => ['php', 'laravel'],
                'prerequisite_concepts' => ['http status codes', 'dependency checks', 'observability'],
                'is_cac_runtime_set'    => true,
                'fixed_complexity'      => null, 'fixed_autonomy' => null, 'fixed_context_fidelity' => null,
                'is_architectural'      => false, 'planning_layer_active' => true,
                'code_execution_config' => ['language' => 'php'],
                'model_response_summary' => 'Returns 200 with uptime/version JSON when DB is reachable, 503 when not. The learner explicitly identifies what they chose NOT to include (e.g. queue depth, latency) and why, demonstrating scope discipline.',
                'time_limit_minutes'    => 45, 'is_published' => true, 'is_active' => true,
            ],
        );

        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $healthTask->id, 'type' => 'code'],
            ['label' => 'Health check endpoint', 'description' => 'The GET /health implementation.', 'is_required' => true, 'display_order' => 1],
        );
        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $healthTask->id, 'type' => 'written_explanation'],
            ['label' => 'Scope justification', 'description' => 'What you checked, what you deliberately left out, and why.', 'is_required' => true, 'display_order' => 2],
        );

        foreach (['low', 'mid', 'high'] as $level) {
            TaskCacVariantModel::updateOrCreate(
                ['task_id' => $healthTask->id, 'complexity_level' => $level],
                [
                    'scenario_text'        => match($level) {
                        'low'  => "Build GET /health. It should return HTTP 200 when the database is reachable and HTTP 503 when it is not. Include a small JSON body with at least a status field.",
                        'mid'  => "Build GET /health. The endpoint must distinguish between 'the app is running' and 'the app can serve patients' — which means checking database connectivity. Return 200 or 503 accordingly, and explain where you drew the line on what else to check.",
                        'high' => "Build GET /health for operations monitoring at a clinic site that has limited IT support. The endpoint should give the IT team a clear signal of system health. Decide what to check, what response shape to return, and what to deliberately omit — then justify your choices.",
                    },
                    'scaffolding_text_low' => "Consider: what is the minimum check that tells you 'the system can serve patients'?",
                    'scaffolding_text_mid' => "Think about what operations staff actually need to see to know if the system is working.",
                    'scaffolding_text_high'=> "No scaffolding.",
                    'context_text_low'     => "The IT team at the second site is your audience.",
                    'context_text_mid'     => "Audience: non-technical IT team at a clinic with limited support.",
                    'context_text_high'    => "Audience: clinic IT team.",
                ],
            );
        }

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $healthTask->id, 'task_dimension_label' => 'Scope discipline'],
            [
                'parent_dimension_id'    => 'dim_design',
                'complexity_level'       => 'mid',
                'criterion_text'         => 'The endpoint checks the right thing (DB connectivity) and the learner explicitly justifies what they left out — scope creep (e.g. checking queue depth, latency, third-party services) is avoided with a reason given.',
                'weight'                 => '1.000', 'dimension_weight' => '0.500',
                'claude_detection_hint'  => 'An endpoint that only checks "is the server up" (not the DB) is developing. One that checks many things without justification for the boundary is also developing — distinguished requires explicit "I didn\'t include X because Y."',
                'distinguished_description' => 'Checks DB connectivity (minimum necessary), explicitly names what was omitted and why the boundary was drawn there.',
                'proficient_description'    => 'Checks DB connectivity with a brief justification.',
                'developing_description'    => 'Either checks the wrong thing (server uptime only) or checks too much without justification.',
                'beginning_description'     => 'No meaningful implementation or a hardcoded 200.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => null,
            ],
        );

        BacklogItemTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Fix position-lookup performance'],
            [
                'description'       => 'Address the slow-query report for GET /queue/{id}/position under concurrent load.',
                'default_priority'  => 'must_have', 'role_tags' => ['backend'],
                'task_id'           => $diagTask->id, 'dependency_item_ids' => null, 'display_order' => 6,
            ],
        );

        BacklogItemTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Add health-check endpoint'],
            [
                'description'       => 'GET /health — DB connectivity check for ops monitoring.',
                'default_priority'  => 'should_have', 'role_tags' => ['backend'],
                'task_id'           => $healthTask->id, 'dependency_item_ids' => null, 'display_order' => 7,
            ],
        );
    }
}
