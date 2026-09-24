<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\AIMediation\Domain\Provider\AiEvaluatorClient;
use Src\Identity\Infrastructure\Persistence\Eloquent\Model\UserModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;
use Tests\TestCase;

class LearnerJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private string $tier = 'proficient';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        DB::table('feature_flags')
            ->whereNotIn('flag_key', ['submission.code_execution'])
            ->update(['is_enabled' => true]);

        $this->app->instance(AiEvaluatorClient::class, new class($this) implements AiEvaluatorClient {
            public function __construct(private LearnerJourneyTest $test) {}

            public function evaluate(string $systemPrompt, array $contentBlocks): array
            {
                $tier = $this->test->currentTier();
                return [
                    'overall_tier'     => $tier,
                    'passes_threshold' => in_array($tier, ['proficient', 'distinguished'], true),
                    'gap_type'         => in_array($tier, ['proficient', 'distinguished'], true) ? null : 'knowledge_gap',
                    'is_uncertain'     => false,
                    'dimensions'       => [
                        ['dimension_id' => 'dim_problem_analysis', 'task_dimension_label' => 'x', 'tier_achieved' => $tier],
                        ['dimension_id' => 'dim_implementation', 'task_dimension_label' => 'y', 'tier_achieved' => $tier],
                    ],
                ];
            }
        });
    }

    public function currentTier(): string
    {
        return $this->tier;
    }

    private function learner(): UserModel
    {
        $user = UserModel::where('email', 'learner@example.com')->firstOrFail();
        $this->actingAs($user);
        $this->post('/learn/enrol', ['entry_category' => 'inexperienced'])->assertRedirect('/learn/diagnostic');

        return $user->fresh();
    }

    private function completeDiagnostic(): void
    {
        $this->get('/learn/diagnostic')->assertOk();
        $sessionId = DB::table('diagnostic_sessions')->exists()
            ? DB::table('learner_sessions')->where('status', 'diagnostic')->value('id')
            : null;
        $this->assertNotNull($sessionId, 'Diagnostic should create a learner session');

        $scenarioId = DB::table('learner_sessions')->where('id', $sessionId)->value('current_scenario_id');
        foreach (DB::table('tasks')->where('scenario_id', $scenarioId)->where('is_published', true)->pluck('id') as $taskId) {
            $this->get("/learn/sessions/{$sessionId}/tasks/{$taskId}/submit")->assertOk();
            $this->post("/learn/sessions/{$sessionId}/tasks/{$taskId}/submit", [
                'layer1_text' => 'A thoughtful explanation of my approach and why.',
                'layer3_code' => '<?php echo "hi";',
            ])->assertRedirect('/learn/diagnostic');
        }

        $this->assertSame('complete', DB::table('diagnostic_sessions')->value('status'));
        $this->get('/learn/diagnostic')->assertOk()->assertSee('Diagnostic Complete');
    }

    public function test_learner_can_finish_diagnostic_and_start_a_project(): void
    {
        $this->learner();
        $this->completeDiagnostic();

        $this->get('/learn')->assertOk();

        $project = ProjectTemplateModel::where('is_published', true)->orderBy('title')->firstOrFail();
        $this->startProject($project);
        $this->get("/learn/{$project->id}/sprint-planning")->assertOk();
    }

    public function test_diagnostic_completes_when_ai_evaluation_is_disabled(): void
    {
        DB::table('feature_flags')->where('flag_key', 'aimediation.claude_evaluation')->update(['is_enabled' => false]);

        $this->learner();
        $this->completeDiagnostic();

        $this->assertSame('Junior', DB::table('diagnostic_sessions')->value('assigned_rank_tier'));
        $this->get('/learn')->assertOk();
    }

    public function test_every_published_project_can_be_played_through_all_scenarios(): void
    {
        $this->learner();
        $this->completeDiagnostic();

        $projects = ProjectTemplateModel::where('is_published', true)->get();
        $this->assertNotEmpty($projects);

        foreach ($projects as $project) {
            $this->startProject($project);
            $sessionId = DB::table('learner_sessions')->where('project_id', $project->id)->value('id');

            $scenarioCount = DB::table('scenario_templates')
                ->where('project_id', $project->id)->where('is_published', true)->where('is_diagnostic', false)->count();

            for ($i = 1; $i <= $scenarioCount; $i++) {
                $this->playCurrentScenario($project, $sessionId);
            }

            $this->assertSame('complete', DB::table('learner_sessions')->where('id', $sessionId)->value('status'),
                "{$project->title} should be complete after {$scenarioCount} scenarios");
        }
    }

    public function test_failed_submission_injects_a_consequence_task(): void
    {
        $this->learner();
        $this->completeDiagnostic();

        $project = ProjectTemplateModel::where('title', 'like', 'MedQueue%')->firstOrFail();
        $this->startProject($project);
        $sessionId = DB::table('learner_sessions')->where('project_id', $project->id)->value('id');

        $this->tier = 'developing';
        $this->planSprint($project, $sessionId);
        $taskId = $this->currentScenarioCoreTaskIds($sessionId)[0];
        $this->submit($sessionId, $taskId);

        $this->assertDatabaseHas('injected_task_cards', ['learner_session_id' => $sessionId, 'card_type' => 'consequence']);
        $this->assertDatabaseHas('gap_flags', ['source_task_id' => $taskId]);
        $this->get("/learn/{$project->id}/sprint-board")->assertOk();
    }

    public function test_consequence_card_must_be_attempted_before_scenario_advances(): void
    {
        $this->learner();
        $this->completeDiagnostic();

        $project = ProjectTemplateModel::where('title', 'like', 'PantryLink%')->firstOrFail();
        $sessionId = $this->startProjectSession($project);
        $scenarioId = $this->currentScenarioId($sessionId);
        $this->planSprint($project, $sessionId);
        [$first, $second] = $this->currentScenarioCoreTaskIds($sessionId);

        $this->tier = 'developing';
        $this->submit($sessionId, $first);
        $consequenceTaskId = DB::table('injected_task_cards')
            ->where('learner_session_id', $sessionId)->where('card_type', 'consequence')->value('injected_task_id');
        $this->assertNotNull($consequenceTaskId);
        $consequenceTitle = DB::table('tasks')->where('id', $consequenceTaskId)->value('title');
        $this->get("/learn/{$project->id}/sprint-board")->assertOk()
            ->assertSee($consequenceTitle, false)
            ->assertSee('data-auto-show="consequence-card,sprint-board"', false);

        $this->tier = 'proficient';
        $this->submit($sessionId, $second);
        $this->assertSame($scenarioId, $this->currentScenarioId($sessionId), 'An open consequence card must hold the scenario');

        $this->submit($sessionId, $consequenceTaskId);
        $this->assertNotSame($scenarioId, $this->currentScenarioId($sessionId));
    }

    public function test_suggestion_card_stays_submittable_after_the_scenario_ends(): void
    {
        $this->learner();
        $this->completeDiagnostic();

        $project = ProjectTemplateModel::where('title', 'like', 'PantryLink%')->firstOrFail();
        $sessionId = $this->startProjectSession($project);
        $this->planSprint($project, $sessionId);
        [$first, $second] = $this->currentScenarioCoreTaskIds($sessionId);

        // Three below-proficient results on the same dimensions form a habit.
        $this->tier = 'developing';
        foreach (range(1, 3) as $attempt) {
            $this->submit($sessionId, $first);
        }
        $this->assertDatabaseHas('habit_flags', ['source_task_id' => $first]);

        $this->tier = 'proficient';
        $consequenceTaskId = DB::table('injected_task_cards')->where('card_type', 'consequence')->value('injected_task_id');
        $this->submit($sessionId, $consequenceTaskId);
        $this->submit($sessionId, $second);

        $suggestionTaskId = DB::table('injected_task_cards')
            ->where('learner_session_id', $sessionId)->where('card_type', 'suggestion')->value('injected_task_id');
        $this->assertNotNull($suggestionTaskId, 'A habit should inject a suggestion card at the scenario transition');
        $this->assertNotSame(DB::table('tasks')->where('id', $suggestionTaskId)->value('scenario_id'), $this->currentScenarioId($sessionId));

        $this->submit($sessionId, $suggestionTaskId);
    }

    public function test_cac_complexity_escalates_after_a_fully_passed_scenario(): void
    {
        $this->learner();
        $this->completeDiagnostic();

        $project = ProjectTemplateModel::where('title', 'like', 'CampusBook%')->firstOrFail();
        $sessionId = $this->startProjectSession($project);
        $learnerId = DB::table('learner_sessions')->where('id', $sessionId)->value('learner_id');
        $before = DB::table('learner_profiles')->where('learner_id', $learnerId)->value('cac_complexity');

        $this->playCurrentScenario($project, $sessionId);

        $after = DB::table('learner_profiles')->where('learner_id', $learnerId)->value('cac_complexity');
        $this->assertSame(['low', 'mid'], [$before, $after]);
    }

    private function startProjectSession(ProjectTemplateModel $project): string
    {
        $this->startProject($project);

        return DB::table('learner_sessions')->where('project_id', $project->id)->value('id');
    }

    private function currentScenarioId(string $sessionId): ?string
    {
        return DB::table('learner_sessions')->where('id', $sessionId)->value('current_scenario_id');
    }

    private function startProject(ProjectTemplateModel $project): void
    {
        $this->get("/learn/{$project->id}")->assertOk();
        $this->post("/learn/{$project->id}/enrol", ['role_id' => $this->eligibleRoleId($project)])->assertSessionHasNoErrors();
        $this->get("/learn/{$project->id}/onboarding")->assertOk();
        $this->post("/learn/{$project->id}/onboarding")->assertRedirect("/learn/{$project->id}/sprint-planning");
    }

    private function playCurrentScenario(ProjectTemplateModel $project, string $sessionId): void
    {
        $scenarioId = DB::table('learner_sessions')->where('id', $sessionId)->value('current_scenario_id');
        $sprintId = $this->planSprint($project, $sessionId);

        $taskIds = $this->currentScenarioCoreTaskIds($sessionId);
        $this->assertNotEmpty($taskIds, "Scenario {$scenarioId} has no core tasks");
        foreach ($taskIds as $taskId) {
            $this->submit($sessionId, $taskId);
        }

        $after = DB::table('learner_sessions')->where('id', $sessionId)->first();
        $this->assertTrue($after->status === 'complete' || $after->current_scenario_id !== $scenarioId,
            "Scenario {$scenarioId} should advance once all core tasks are submitted (status {$after->status})");

        $this->post("/learn/{$project->id}/sprint-board/submit", ['sprint_id' => $sprintId])->assertSessionHasNoErrors();
    }

    private function planSprint(ProjectTemplateModel $project, string $sessionId): string
    {
        $this->get("/learn/{$project->id}/sprint-planning")->assertOk();
        $sprintId = DB::table('learner_sprints')->where('learner_session_id', $sessionId)->where('status', 'planning')->value('id');
        $this->assertNotNull($sprintId);

        $taskIds = $this->currentScenarioCoreTaskIds($sessionId);
        $items = DB::table('learner_backlog_items')
            ->join('backlog_item_templates', 'backlog_item_templates.id', '=', 'learner_backlog_items.template_item_id')
            ->where('learner_backlog_items.learner_session_id', $sessionId)
            ->whereIn('backlog_item_templates.task_id', $taskIds)
            ->pluck('learner_backlog_items.id');
        $this->assertCount(count($taskIds), $items, 'Every core task of the current scenario needs a backlog item');

        foreach ($items as $itemId) {
            $this->post("/learn/{$project->id}/sprint-planning/items/{$itemId}/move", ['sprint_id' => $sprintId]);
        }
        $this->post("/learn/{$project->id}/sprint-planning/goal", ['sprint_id' => $sprintId, 'goal' => 'Ship the scenario work safely.']);
        $this->post("/learn/{$project->id}/sprint-planning/confirm", ['sprint_id' => $sprintId])
            ->assertRedirect("/learn/{$project->id}/sprint-board");
        $this->get("/learn/{$project->id}/sprint-board")->assertOk();

        return $sprintId;
    }

    /** @return string[] */
    private function currentScenarioCoreTaskIds(string $sessionId): array
    {
        $scenarioId = DB::table('learner_sessions')->where('id', $sessionId)->value('current_scenario_id');

        return DB::table('tasks')->where('scenario_id', $scenarioId)
            ->where('is_published', true)->where('is_active', true)->where('task_type', 'core')
            ->orderBy('sequence_order')->pluck('id')->all();
    }

    private function submit(string $sessionId, string $taskId): void
    {
        $this->travel(1)->minutes();
        $this->get("/learn/sessions/{$sessionId}/tasks/{$taskId}/submit")->assertOk();
        $this->post("/learn/sessions/{$sessionId}/tasks/{$taskId}/submit", [
            'layer1_text' => 'My approach, the trade-offs I weighed, and why I chose it.',
            'layer3_code' => '<?php // implementation',
        ])->assertSessionHasNoErrors()->assertRedirect();
    }

    private function eligibleRoleId(ProjectTemplateModel $project): string
    {
        $tags = $project->specialization_tags;
        foreach (DB::table('role_definitions')->where('min_years_experience', 0)->get() as $role) {
            if (array_intersect($tags, json_decode($role->specialization_tags, true)) !== []) {
                return $role->id;
            }
        }
        $this->fail('No entry-level role for project ' . $project->title);
    }
}
