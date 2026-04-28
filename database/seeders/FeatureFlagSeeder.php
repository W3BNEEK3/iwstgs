<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds all 17 feature flags as disabled.
 *
 * Rule: every significant feature is gated behind a flag seeded as FALSE.
 * Enable flags only as phases are completed and verified — never before.
 * This lets us deploy dead code safely without affecting learners.
 *
 * See Implementation Plan Appendix A for the enable schedule per phase.
 */
class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $flags = [
            ['flag_key' => 'admin.content_management',        'module' => 'Content',       'description' => 'Enables the admin content management UI for projects, scenarios, and tasks.'],
            ['flag_key' => 'organisations.multi_tenancy',      'module' => 'Organizations', 'description' => 'Enables organisation-scoped data filtering and multi-tenancy enforcement.'],
            ['flag_key' => 'simulation.project_catalogue',     'module' => 'SimExecution',  'description' => 'Enables the learner-facing project catalogue and enrolment flow.'],
            ['flag_key' => 'simulation.diagnostic_assessment', 'module' => 'SimExecution',  'description' => 'Enables the diagnostic scenario pathway for initial rank assignment.'],
            ['flag_key' => 'simulation.session_onboarding',    'module' => 'SimExecution',  'description' => 'Enables scenario briefing, situation trigger display, and induction flow.'],
            ['flag_key' => 'simulation.sprint_planning',       'module' => 'SimExecution',  'description' => 'Enables the sprint planning view with backlog and sprint goal interaction.'],
            ['flag_key' => 'simulation.sprint_board',          'module' => 'SimExecution',  'description' => 'Enables the Kanban sprint board and Artifact Vault access.'],
            ['flag_key' => 'simulation.task_submission',       'module' => 'Submission',    'description' => 'Enables the task submission form and four-layer submission assembly.'],
            ['flag_key' => 'submission.code_execution',        'module' => 'Submission',    'description' => 'Enables Docker-based code execution. Leave disabled until Phase 11.'],
            ['flag_key' => 'aimediation.claude_evaluation',    'module' => 'AIMediation',   'description' => 'Enables live Claude API evaluation. When disabled, submissions queue for human review.'],
            ['flag_key' => 'adaptive.consequence_tasks',       'module' => 'EvalEngine',    'description' => 'Enables automatic injection of consequence task cards on failed submissions.'],
            ['flag_key' => 'adaptive.suggestion_tasks',        'module' => 'EvalEngine',    'description' => 'Enables habit pattern detection and suggestion task injection.'],
            ['flag_key' => 'adaptive.rank_management',         'module' => 'EvalEngine',    'description' => 'Enables automatic rank escalation, de-escalation, and mismatch detection.'],
            ['flag_key' => 'adaptive.scenario_transitions',    'module' => 'EvalEngine',    'description' => 'Enables automatic scenario transitions and dimension-targeted routing.'],
            ['flag_key' => 'reporting.learner_profile',        'module' => 'Reporting',     'description' => 'Enables the learner profile dashboard with radar chart and competency summary.'],
            ['flag_key' => 'reporting.qualification_report',   'module' => 'Reporting',     'description' => 'Enables the role qualification report comparing learner scores to role thresholds.'],
            ['flag_key' => 'reporting.admin_analytics',        'module' => 'Reporting',     'description' => 'Enables the admin reporting views for learner progress and task performance.'],
        ];

        foreach ($flags as $flag) {
            DB::table('feature_flags')->insertOrIgnore(array_merge($flag, [
                'is_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
