<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\AIMediation\Domain\Exceptions\AiProviderException;
use Src\AIMediation\Domain\Provider\AiEvaluatorClient;
use Src\AIMediation\Domain\Provider\AiTextGeneratorClient;
use Src\Guidance\Application\Service\GuideMessageValidator;
use Src\Identity\Infrastructure\Persistence\Eloquent\Model\UserModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;
use Tests\TestCase;

/** Tiroco's learner-aware messages (design doc v2-05). */
class AiGuideTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public string $tier = 'developing';
    public ?string $aiReply = null;
    public bool $aiFails = false;
    public int $aiCalls = 0;

    private UserModel $learner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        DB::table('feature_flags')->where('flag_key', '!=', 'submission.code_execution')->update(['is_enabled' => true]);
        DB::table('feature_flags')->where('flag_key', 'simulation.diagnostic_assessment')->update(['is_enabled' => false]);

        $this->app->instance(AiEvaluatorClient::class, new class($this) implements AiEvaluatorClient {
            public function __construct(private AiGuideTest $test) {}

            public function evaluate(string $systemPrompt, array $contentBlocks): array
            {
                $pass = in_array($this->test->tier, ['proficient', 'distinguished'], true);

                return [
                    'overall_tier'     => $this->test->tier,
                    'passes_threshold' => $pass,
                    'gap_type'         => $pass ? null : 'knowledge_gap',
                    'is_uncertain'     => false,
                    'dimensions'       => [
                        ['dimension_id' => 'dim_problem_analysis', 'task_dimension_label' => 'Edge cases', 'tier_achieved' => $this->test->tier,
                         'criteria_met' => [], 'criteria_missed' => $pass ? [] : ['Rejects a negative quantity with a clear error']],
                        ['dimension_id' => 'dim_communication', 'task_dimension_label' => 'Explanation', 'tier_achieved' => $this->test->tier,
                         'criteria_met' => [], 'criteria_missed' => []],
                    ],
                ];
            }
        });

        $this->app->instance(AiTextGeneratorClient::class, new class($this) implements AiTextGeneratorClient {
            public function __construct(private AiGuideTest $test) {}

            public function generate(string $systemPrompt, array $contentBlocks): string
            {
                $this->test->aiCalls++;
                if ($this->test->aiFails) {
                    throw new AiProviderException('fake', 'down');
                }

                return $this->test->aiReply
                    ?? '{"title": "Focus on the negative case", "body": "Your last two tries missed how a negative quantity is handled. Re-read the brief with just that in mind, then say in your explanation how you covered it."}';
            }
        });

        $this->learner = UserModel::where('email', 'learner@example.com')->firstOrFail();
        $this->actingAs($this->learner);
        $this->post('/learn/enrol', ['entry_category' => 'inexperienced']);
    }

    // ---- Observed nudges ------------------------------------------------------

    public function test_a_second_failed_attempt_queues_a_nudge_that_the_ai_writes_at_delivery(): void
    {
        [, $sessionId, $taskId] = $this->startPantryLink();

        $this->submit($sessionId, $taskId);
        $this->assertDatabaseMissing('guide_messages', ['trigger_key' => 'repeat-fail']);

        $this->submit($sessionId, $taskId);
        $this->assertDatabaseHas('guide_messages', ['trigger_key' => 'repeat-fail', 'context_ref' => $taskId, 'status' => 'pending']);
        $facts = json_decode(DB::table('guide_messages')->where('trigger_key', 'repeat-fail')->value('facts'), true);
        $this->assertSame(['Rejects a negative quantity with a clear error'], $facts['missed_criteria']);

        $message = $this->getJson('/guide/next?page=task-submit')->assertOk()->json('message');

        $this->assertSame('nudge', $message['kind']);
        $this->assertSame('Focus on the negative case', $message['title']);
        $this->assertStringContainsString("/learn/sessions/{$sessionId}/tasks/{$taskId}/submit", $message['ctaUrl']);
        $this->assertSame('You have tried this task more than once without a pass yet.', $message['why']);
        $this->assertDatabaseHas('guide_messages', ['trigger_key' => 'repeat-fail', 'generated_by' => 'ai', 'status' => 'shown']);

        // A third failure on the same task doesn't repeat the nudge (cooldown per task).
        $this->submit($sessionId, $taskId);
        $this->assertSame(1, DB::table('guide_messages')->where('trigger_key', 'repeat-fail')->count());
    }

    public function test_unsafe_or_failed_ai_output_falls_back_to_the_authored_text(): void
    {
        $this->aiReply = '{"title": "Try this", "body": "Just add `if (qty < 0) return res.status(422);` to your handler and you are done."}';
        $this->queueNudge('repeat-fail', 'Authored title', 'The authored fallback body that is always safe to show.');

        $message = $this->getJson('/guide/next')->json('message');
        $this->assertSame('Authored title', $message['title']);
        $this->assertDatabaseHas('guide_messages', ['title' => 'Authored title', 'generated_by' => 'fallback']);

        $this->aiFails = true;
        $this->queueNudge('thin-explanations', 'Second authored title', 'Another authored body that is always safe to show.');
        $this->assertSame('Second authored title', $this->getJson('/guide/next')->json('message.title'));
    }

    public function test_three_weak_results_on_a_skill_recommend_one_curated_resource_until_opted_out(): void
    {
        [, $sessionId, $taskId] = $this->startPantryLink();
        foreach (range(1, 3) as $i) {
            $this->submit($sessionId, $taskId);
        }

        $resourceMessage = DB::table('guide_messages')->where('trigger_key', 'weak-dimension')->first();
        $this->assertNotNull($resourceMessage);
        $resource = DB::table('guide_resources')->where('id', $resourceMessage->resource_id)->first();
        $this->assertContains($resourceMessage->context_ref, json_decode($resource->dimensions, true));
        $this->assertSame($resource->url, $resourceMessage->cta_url, 'Links come from the curated list, never from the AI');

        $this->post("/guide/messages/{$resourceMessage->id}/opt-out", ['reason' => 'already_use'])->assertNoContent();
        $this->assertDatabaseHas('guide_resource_opt_outs', ['resource_id' => $resource->id, 'reason' => 'already_use']);
    }

    public function test_the_task_page_reports_a_learner_who_seems_stuck(): void
    {
        [, $sessionId, $taskId] = $this->startPantryLink();

        $page = $this->get("/learn/sessions/{$sessionId}/tasks/{$taskId}/submit")->assertOk()->getContent();
        $this->assertStringContainsString('/guide/stuck', str_replace('\\', '', $page), 'The task page arms the stuck timer');

        $this->post('/guide/stuck', ['session' => $sessionId, 'task' => $taskId, 'minutes' => 5])->assertNoContent();
        $this->assertDatabaseMissing('guide_messages', ['trigger_key' => 'stuck-idle']);

        $this->post('/guide/stuck', ['session' => (string) Str::uuid(), 'task' => $taskId, 'minutes' => 30])->assertNoContent();
        $this->assertDatabaseMissing('guide_messages', ['trigger_key' => 'stuck-idle']);

        $this->post('/guide/stuck', ['session' => $sessionId, 'task' => $taskId, 'minutes' => 25])->assertNoContent();
        $this->assertDatabaseHas('guide_messages', ['trigger_key' => 'stuck-idle', 'context_ref' => $taskId]);
    }

    public function test_welcome_back_after_five_days_away(): void
    {
        $this->getJson('/guide/next?page=profile');
        DB::table('user_guide_preferences')->update(['last_active_at' => now()->subDays(6)]);
        DB::table('guide_messages')->delete();

        $message = $this->getJson('/guide/next?page=settings')->json('message');
        $this->assertSame('nudge', $message['kind']);
        $this->assertDatabaseHas('guide_messages', ['trigger_key' => 'welcome-back']);
    }

    public function test_a_calm_page_offers_one_tip_a_day(): void
    {
        $message = $this->getJson('/guide/next?page=catalogue')->json('message');
        $this->assertSame('tip', $message['kind']);
        $this->assertNotNull(DB::table('guide_messages')->where('trigger_key', 'quiet-moment')->value('tip_id'));

        $this->getJson('/guide/next?page=catalogue')->assertJson(['message' => null]);
    }

    // ---- Frequency rules and learner controls ---------------------------------

    public function test_one_message_per_page_a_daily_cap_and_announcements_first(): void
    {
        foreach (['repeat-fail', 'thin-explanations', 'stuck-idle', 'welcome-back'] as $i => $key) {
            $this->queueNudge($key, "Nudge {$i}", 'A perfectly reasonable nudge body for the learner.', status: 'ready');
        }
        $announcementId = $this->publishedAnnouncement('New: submission history');

        $this->assertSame('New: submission history', $this->getJson('/guide/next')->json('message.title'));
        $this->assertSame('nudge', $this->getJson('/guide/next')->json('message.kind'));
        $this->getJson('/guide/next');
        $this->getJson('/guide/next');
        $this->getJson('/guide/next')->assertJson(['message' => null]);

        $this->assertSame(3, DB::table('guide_messages')->where('kind', 'nudge')->where('status', 'shown')->count(), 'Daily cap of 3');
        $this->assertSame(1, DB::table('guide_messages')->where('announcement_id', $announcementId)->count());
    }

    public function test_the_check_in_is_a_quiet_zone(): void
    {
        $this->queueNudge('thin-explanations', 'Not now', 'A perfectly reasonable nudge body for the learner.', status: 'ready');

        $this->getJson('/guide/next?page=diagnostic')->assertJson(['message' => null]);
        $this->assertSame('Not now', $this->getJson('/guide/next?page=profile')->json('message.title'));
    }

    public function test_learners_choose_which_kinds_of_message_pop_up(): void
    {
        $this->get('/learn/settings')->assertOk()->assertSee('Hints when I seem stuck')->assertSee('Tips on using Areyna well');

        $this->post('/guide/kinds', ['kinds' => ['nudge']])->assertRedirect();
        $this->getJson('/guide/next?page=catalogue')->assertJson(['message' => null]);
        $this->assertDatabaseMissing('guide_messages', ['kind' => 'tip']);

        $this->post('/guide/disable');
        $this->queueNudge('stuck-idle', 'Queued before', 'A perfectly reasonable nudge body for the learner.', status: 'ready');
        $this->getJson('/guide/next')->assertJson(['message' => null]);
    }

    public function test_waving_messages_away_quietly_pauses_that_kind(): void
    {
        foreach (range(1, 3) as $i) {
            $id = $this->queueNudge('stuck-idle', "Nudge {$i}", 'A perfectly reasonable nudge body for the learner.', status: 'ready', context: "task-{$i}");
            $this->getJson('/guide/next')->assertJsonPath('message.id', $id);
            $this->post("/guide/messages/{$id}/dismiss")->assertNoContent();
        }

        $paused = json_decode(DB::table('user_guide_preferences')->value('paused_until'), true);
        $this->assertArrayHasKey('nudge', $paused);

        $this->queueNudge('stuck-idle', 'While paused', 'A perfectly reasonable nudge body for the learner.', status: 'ready', context: 'task-4');
        $this->getJson('/guide/next')->assertJson(['message' => null]);
    }

    public function test_rating_is_recorded_only_for_the_owner(): void
    {
        $id = $this->queueNudge('stuck-idle', 'Rate me', 'A perfectly reasonable nudge body for the learner.', status: 'ready');
        $this->post("/guide/messages/{$id}/rate", ['helpful' => true])->assertNoContent();
        $this->assertDatabaseHas('guide_messages', ['id' => $id, 'rating' => 'helpful']);

        $this->actingAs(UserModel::where('email', 'admin@example.com')->firstOrFail());
        $this->post("/guide/messages/{$id}/rate", ['helpful' => false])->assertNoContent();
        $this->assertDatabaseHas('guide_messages', ['id' => $id, 'rating' => 'helpful']);
    }

    // ---- Announcements ---------------------------------------------------------

    public function test_admin_drafts_generates_and_publishes_an_announcement_learners_see_once(): void
    {
        $this->actingAs(UserModel::where('email', 'admin@example.com')->firstOrFail());
        $this->get('/admin/guide')->assertOk()->assertSee('Second failed attempt on a task');
        $this->get('/admin/guide/tips')->assertOk()->assertSee('Write your explanation before you polish the code.');
        $this->get('/admin/guide/resources')->assertOk()->assertSee('MDN Web Docs');

        $this->post('/admin/guide/announcements', [
            'internal_title' => 'Submission history on profile',
            'notes'          => 'Added SubmissionHistoryPanel to the profile; lists every attempt with tier.',
        ])->assertRedirect();
        $id = DB::table('feature_announcements')->value('id');

        $this->post("/admin/guide/announcements/{$id}/status", ['status' => 'published'])
            ->assertSessionHas('error');
        $this->assertDatabaseHas('feature_announcements', ['id' => $id, 'status' => 'draft']);

        $this->aiReply = '{"title": "See every attempt on your profile", "body": "Your profile now lists each attempt you made on a task, so you can spot how your work improved over time."}';
        $this->post("/admin/guide/announcements/{$id}/generate")->assertSessionHas('success');
        $this->post("/admin/guide/announcements/{$id}/status", ['status' => 'published'])->assertSessionHas('success');

        $this->actingAs($this->learner);
        $this->assertSame('See every attempt on your profile', $this->getJson('/guide/next')->json('message.title'));
        $this->getJson('/guide/next')->assertJsonMissing(['title' => 'See every attempt on your profile']);
        $this->get('/guide/whats-new')->assertOk()->assertSee('See every attempt on your profile');
    }

    public function test_announcements_tied_to_a_flag_only_show_while_it_is_on(): void
    {
        $this->publishedAnnouncement('Flagged feature', flag: 'reporting.qualification_report');
        DB::table('feature_flags')->where('flag_key', 'reporting.qualification_report')->update(['is_enabled' => false]);
        cache()->flush();

        $this->getJson('/guide/next')->assertJson(['message' => null]);
        $this->get('/guide/whats-new')->assertOk()->assertDontSee('Flagged feature');
    }

    public function test_announcement_links_must_stay_inside_areyna(): void
    {
        $this->actingAs(UserModel::where('email', 'admin@example.com')->firstOrFail());

        foreach (['https://evil.example', '//evil.example/path', 'javascript:alert(1)'] as $link) {
            $this->post('/admin/guide/announcements', ['internal_title' => 'x', 'notes' => 'y', 'link_url' => $link])
                ->assertSessionHasErrors('link_url');
        }
        $this->post('/admin/guide/announcements', ['internal_title' => 'x', 'notes' => 'y', 'link_url' => '/learn/profile'])
            ->assertSessionHasNoErrors();
    }

    public function test_learners_cannot_reach_the_admin_guide(): void
    {
        $this->get('/admin/guide')->assertForbidden();
    }

    public function test_admin_can_switch_a_trigger_off(): void
    {
        $this->actingAs(UserModel::where('email', 'admin@example.com')->firstOrFail());
        $this->put('/admin/guide/settings', [
            'daily_cap' => 2,
            'triggers'  => ['quiet-moment' => ['enabled' => 0, 'cooldown_hours' => 24]],
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->learner);
        $this->getJson('/guide/next?page=catalogue')->assertJson(['message' => null]);
        $this->assertDatabaseMissing('guide_messages', ['trigger_key' => 'quiet-moment']);
    }

    // ---- The validator ----------------------------------------------------------

    public function test_the_validator_rejects_code_links_and_jargon(): void
    {
        $validator = new GuideMessageValidator();

        $this->assertNotNull($validator->parse('{"title": "Take it step by step", "body": "Write down the smallest first step and do just that, then check it against the brief."}'));
        $this->assertNotNull($validator->parse("```json\n{\"title\": \"Fenced\", \"body\": \"Models sometimes wrap JSON in a fence, which is fine to unwrap.\"}\n```"));
        $this->assertNull($validator->parse('{"skip": true}'));
        $this->assertNull($validator->parse('not json'));
        $this->assertNull($validator->parse('{"title": "Link", "body": "Have a look at https://example.com for the answer to this task."}'));
        $this->assertNull($validator->parse('{"title": "Jargon", "body": "Your CAC autonomy is low, so the guidance is higher for a while."}'));
        $this->assertNull($validator->parse('{"title": "Code", "body": "Try const total = items.reduce((a, b) => a + b, 0); in your handler."}'));
        $this->assertNull($validator->parse('{"title": "Too long", "body": "' . str_repeat('word ', 90) . '"}'));
    }

    // ---- Helpers ----------------------------------------------------------------

    private function queueNudge(string $trigger, string $title, string $body, string $status = 'pending', ?string $context = null): string
    {
        $id = (string) Str::uuid();
        DB::table('guide_messages')->insert([
            'id' => $id, 'user_id' => $this->learner->id, 'kind' => 'nudge', 'trigger_key' => $trigger,
            'context_ref' => $context, 'facts' => '{}', 'title' => $title, 'body' => $body, 'status' => $status,
            'generated_by' => 'authored', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    private function publishedAnnouncement(string $title, ?string $flag = null): string
    {
        $id = (string) Str::uuid();
        DB::table('feature_announcements')->insert([
            'id' => $id, 'internal_title' => $title, 'notes' => 'notes', 'title' => $title,
            'body' => 'A short note about something new for learners.', 'feature_flag' => $flag,
            'status' => 'published', 'published_at' => now()->addSecond(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    /** @return array{0: ProjectTemplateModel, 1: string, 2: string} project, session id, first core task id */
    private function startPantryLink(): array
    {
        $project = ProjectTemplateModel::where('title', 'like', 'PantryLink%')->firstOrFail();
        $this->get("/learn/{$project->id}")->assertOk();
        $this->post("/learn/{$project->id}/enrol", ['role_id' => $this->eligibleRoleId($project)])->assertSessionHasNoErrors();
        $this->post("/learn/{$project->id}/onboarding");
        $sessionId = DB::table('learner_sessions')->where('project_id', $project->id)->value('id');

        $this->get("/learn/{$project->id}/sprint-planning")->assertOk();
        $sprintId = DB::table('learner_sprints')->where('learner_session_id', $sessionId)->where('status', 'planning')->value('id');
        $scenarioId = DB::table('learner_sessions')->where('id', $sessionId)->value('current_scenario_id');
        $taskIds = DB::table('tasks')->where('scenario_id', $scenarioId)->where('is_published', true)
            ->where('task_type', 'core')->orderBy('sequence_order')->pluck('id')->all();
        $items = DB::table('learner_backlog_items')
            ->join('backlog_item_templates', 'backlog_item_templates.id', '=', 'learner_backlog_items.template_item_id')
            ->where('learner_backlog_items.learner_session_id', $sessionId)
            ->whereIn('backlog_item_templates.task_id', $taskIds)
            ->pluck('learner_backlog_items.id');
        foreach ($items as $itemId) {
            $this->post("/learn/{$project->id}/sprint-planning/items/{$itemId}/move", ['sprint_id' => $sprintId]);
        }
        $this->post("/learn/{$project->id}/sprint-planning/goal", ['sprint_id' => $sprintId, 'goal' => 'Log donations safely.']);
        $this->post("/learn/{$project->id}/sprint-planning/confirm", ['sprint_id' => $sprintId]);

        return [$project, $sessionId, $taskIds[0]];
    }

    private function submit(string $sessionId, string $taskId): void
    {
        $this->travel(1)->minutes();
        $this->post("/learn/sessions/{$sessionId}/tasks/{$taskId}/submit", [
            'layer1_text' => 'Short note.',
            'layer3_code' => '<?php // implementation',
        ])->assertSessionHasNoErrors()->assertRedirect();
    }

    private function eligibleRoleId(ProjectTemplateModel $project): string
    {
        foreach (DB::table('role_definitions')->where('min_years_experience', 0)->get() as $role) {
            if (array_intersect($project->specialization_tags, json_decode($role->specialization_tags, true)) !== []) {
                return $role->id;
            }
        }
        $this->fail('No entry-level role for ' . $project->title);
    }
}
