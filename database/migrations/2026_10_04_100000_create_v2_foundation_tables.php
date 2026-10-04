<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Areyna v2-1 foundation (design doc v2-01 §1): projects that are built for
 * real in the learner's own GitHub repository.
 *
 * - project_templates.track — classic (today's paste-in projects), build, or
 *   work_experience; plus the per-project regression consequence task.
 * - project_stack_variants — the same project in different stacks.
 * - learner_sessions.stack_variant_id — the stack a learner chose.
 * - tasks.task_type gains `milestone` (a step of the build) and `review`.
 * - task_variant_specs — per-stack brief addendum and acceptance test IDs.
 * - scenario_events / learner_scenario_events — scripted story events.
 * - github_connections, learner_repositories, github_webhook_deliveries.
 * - submission_packages gains commit/CI columns.
 *
 * Also inserts the v2 feature flags (all off: classic projects are untouched
 * until an admin turns them on) and the "Solo Developer" role that Build
 * projects enrol learners into.
 */
return new class extends Migration {
    private const TASK_TYPES = ['core', 'consequence', 'suggestion', 'diagnostic_scenario', 'diagnostic_consequence'];

    public function up(): void
    {
        Schema::table('project_templates', function (Blueprint $table) {
            $table->string('track', 30)->default('classic')->after('project_type');
            $table->uuid('regression_consequence_task_id')->nullable();
        });

        Schema::create('project_stack_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->string('key', 50);
            $table->string('name', 100);
            $table->json('languages');
            $table->unsignedTinyInteger('difficulty')->default(1);
            $table->string('min_rank_tier', 20)->nullable();
            $table->unsignedSmallInteger('min_rank_level')->nullable();
            $table->string('template_repo', 200);
            $table->string('reference_repo', 200)->nullable();
            $table->string('acceptance_ref', 100)->nullable();
            $table->string('workflow_sha256', 64)->nullable(); // expected .github/workflows/areyna.yml
            $table->text('setup_notes')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'key']);
            $table->foreign('project_id')->references('id')->on('project_templates')->cascadeOnDelete();
        });

        Schema::table('learner_sessions', function (Blueprint $table) {
            $table->uuid('stack_variant_id')->nullable();
            $table->foreign('stack_variant_id')->references('id')->on('project_stack_variants')->nullOnDelete();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->enum('task_type', [...self::TASK_TYPES, 'milestone', 'review'])->default('core')->change();
        });

        Schema::create('task_variant_specs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id');
            $table->uuid('stack_variant_id');
            $table->text('brief_addendum')->nullable();
            $table->json('acceptance_tests'); // test IDs this milestone introduces
            $table->json('hints')->nullable();
            $table->string('reference_tag', 50)->nullable();
            $table->timestamps();

            $table->unique(['task_id', 'stack_variant_id']);
            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->foreign('stack_variant_id')->references('id')->on('project_stack_variants')->cascadeOnDelete();
        });

        Schema::create('scenario_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('scenario_id');
            $table->string('trigger', 20);          // scenario_start | after_task
            $table->uuid('trigger_task_id')->nullable();
            $table->string('event_type', 30);       // stakeholder_message | requirement_change | incident | teammate_pr | review_request
            $table->json('role_tags')->nullable();
            $table->json('payload');                // {sender, role, text}
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->foreign('scenario_id')->references('id')->on('scenario_templates')->cascadeOnDelete();
            $table->foreign('trigger_task_id')->references('id')->on('tasks')->nullOnDelete();
        });

        Schema::create('learner_scenario_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_session_id');
            $table->uuid('scenario_event_id');
            $table->timestamp('fired_at')->useCurrent();
            $table->json('result')->nullable();

            $table->unique(['learner_session_id', 'scenario_event_id']);
            $table->foreign('learner_session_id')->references('id')->on('learner_sessions')->cascadeOnDelete();
            $table->foreign('scenario_event_id')->references('id')->on('scenario_events')->cascadeOnDelete();
        });

        Schema::create('github_connections', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->unsignedBigInteger('github_user_id')->unique();
            $table->string('github_login', 100);
            $table->timestamp('connected_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('learner_repositories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_session_id')->unique();
            $table->unsignedBigInteger('github_repo_id');
            $table->string('full_name', 200);
            $table->unsignedBigInteger('installation_id');
            $table->string('default_branch', 100);
            $table->string('template_repo', 200);
            $table->string('status', 20)->default('linked'); // linked | access_lost
            $table->string('start_sha', 40);                 // default-branch head when linked
            $table->string('last_accepted_sha', 40)->nullable();
            $table->timestamps();

            $table->index('github_repo_id');
            $table->foreign('learner_session_id')->references('id')->on('learner_sessions')->cascadeOnDelete();
        });

        Schema::create('github_webhook_deliveries', function (Blueprint $table) {
            $table->string('delivery_id', 100)->primary();
            $table->string('event', 50);
            $table->timestamp('received_at')->useCurrent();
        });

        Schema::table('submission_packages', function (Blueprint $table) {
            $table->string('source', 20)->default('paste');    // paste | commit | pull_request
            $table->string('commit_sha', 40)->nullable();
            $table->string('base_sha', 40)->nullable();
            $table->unsignedInteger('pr_number')->nullable();
            $table->string('ci_status', 20)->nullable();       // pending | passed | failed | errored | not_run
            $table->string('ci_run_url', 300)->nullable();
            $table->json('ci_report')->nullable();
            $table->json('diff_summary')->nullable();
            $table->index(['commit_sha', 'ci_status']);
        });

        foreach ([
            ['sourcecontrol.github',             'SourceControl', 'Connect GitHub, repository linking, GitHub webhooks and milestone submissions from commits.'],
            ['tracks.build',                     'SimExecution',  'Shows Build-track projects (built for real in the learner\'s own GitHub repo) in the catalogue.'],
            ['tracks.work_experience',           'SimExecution',  'Shows Work Experience-track projects in the catalogue.'],
            ['scenario.scripted_events',         'SimExecution',  'Fires authored scenario events (stakeholder messages, requirement changes, incidents) during projects.'],
            ['sourcecontrol.pr_review_comments', 'SourceControl', 'Posts the AI review as comments on the learner\'s GitHub pull request (a copy is always kept in Areyna).'],
            ['hosting.publish',                  'SourceControl', 'Offers free Areyna hosting for a finished Build project.'],
        ] as [$key, $module, $description]) {
            DB::table('feature_flags')->insertOrIgnore([
                'flag_key' => $key, 'module' => $module, 'description' => $description,
                'is_enabled' => false, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // The guide's "criteria missed" tip pointed at feedback classic projects never show;
        // it now speaks to Build milestones, which do. Only replaced if an admin hasn't edited it.
        DB::table('guide_tips')->where('key', 'criteria-missed')
            ->where('text', 'On a result, read "criteria missed" first. It is the quickest route to a pass on the next attempt.')
            ->update([
                'text'       => 'When a milestone doesn\'t pass, read the first failing test and the reviewer\'s notes before changing anything. They point at the quickest route to a pass.',
                'pages'      => json_encode(['evaluation-result', 'milestone']),
                'updated_at' => now(),
            ]);

        if (! DB::table('role_definitions')->where('title', 'Solo Developer')->exists()) {
            DB::table('role_definitions')->insert([
                'id'                   => (string) Str::uuid(),
                'title'                => 'Solo Developer',
                'specialization_tags'  => json_encode(['build']),
                'min_years_experience' => 0,
                'dimension_weights'    => json_encode([
                    'dim_problem_analysis' => 0.15, 'dim_design' => 0.15, 'dim_implementation' => 0.30,
                    'dim_testing' => 0.15, 'dim_debugging' => 0.10, 'dim_communication' => 0.15,
                ]),
                'dimension_thresholds' => json_encode([
                    'dim_problem_analysis' => 'developing', 'dim_design' => 'developing',
                    'dim_implementation' => 'proficient', 'dim_testing' => 'developing',
                    'dim_debugging' => 'developing', 'dim_communication' => 'developing',
                ]),
                'is_lead_role'         => false,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('feature_flags')->whereIn('flag_key', [
            'sourcecontrol.github', 'tracks.build', 'tracks.work_experience', 'scenario.scripted_events',
            'sourcecontrol.pr_review_comments', 'hosting.publish',
        ])->delete();

        Schema::table('submission_packages', function (Blueprint $table) {
            $table->dropIndex(['commit_sha', 'ci_status']);
            $table->dropColumn(['source', 'commit_sha', 'base_sha', 'pr_number', 'ci_status', 'ci_run_url', 'ci_report', 'diff_summary']);
        });
        Schema::dropIfExists('github_webhook_deliveries');
        Schema::dropIfExists('learner_repositories');
        Schema::dropIfExists('github_connections');
        Schema::dropIfExists('learner_scenario_events');
        Schema::dropIfExists('scenario_events');
        Schema::dropIfExists('task_variant_specs');
        Schema::table('tasks', function (Blueprint $table) {
            $table->enum('task_type', self::TASK_TYPES)->default('core')->change();
        });
        Schema::table('learner_sessions', function (Blueprint $table) {
            $table->dropForeign(['stack_variant_id']);
            $table->dropColumn('stack_variant_id');
        });
        Schema::dropIfExists('project_stack_variants');
        Schema::table('project_templates', function (Blueprint $table) {
            $table->dropColumn(['track', 'regression_consequence_task_id']);
        });
    }
};
