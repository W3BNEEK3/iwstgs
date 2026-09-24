<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Src\AIMediation\Domain\Exceptions\AiProviderException;
use Src\AIMediation\Domain\Provider\AiTextGeneratorClient;
use Src\Identity\Infrastructure\Persistence\Eloquent\Model\UserModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;
use Tests\TestCase;

class GuidanceTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public int $aiCalls = 0;
    public bool $aiFails = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        DB::table('feature_flags')->where('flag_key', '!=', 'submission.code_execution')->update(['is_enabled' => true]);
        DB::table('feature_flags')->where('flag_key', 'simulation.diagnostic_assessment')->update(['is_enabled' => false]);

        $this->app->instance(AiTextGeneratorClient::class, new class($this) implements AiTextGeneratorClient {
            public function __construct(private GuidanceTest $test) {}

            public function generate(string $systemPrompt, array $contentBlocks): string
            {
                $this->test->aiCalls++;
                if ($this->test->aiFails) {
                    throw new AiProviderException('fake', 'down');
                }

                return 'You will help a food bank stop wasting food.';
            }
        });

        $user = UserModel::where('email', 'learner@example.com')->firstOrFail();
        $this->actingAs($user);
        $this->post('/learn/enrol', ['entry_category' => 'inexperienced']);
    }

    public function test_page_tips_show_until_dismissed(): void
    {
        $this->get('/learn')->assertOk()->assertSee('Choosing a project')->assertSee('data-auto-show="catalogue"', false);

        $this->post('/guide/steps/catalogue/dismiss')->assertNoContent();

        $this->get('/learn')->assertOk()
            ->assertSee('Choosing a project')
            ->assertSee('data-auto-show=""', false);
    }

    public function test_turning_the_guide_off_stops_auto_tips_but_keeps_the_launcher(): void
    {
        $this->post('/guide/disable')->assertNoContent();

        $this->get('/learn')->assertOk()
            ->assertSee('data-auto-show=""', false)
            ->assertSee('Open the guide for this page');

        $this->get('/learn/settings')->assertOk()->assertSee('Turn the guide back on');
        $this->post('/guide/enable')->assertRedirect();
        $this->get('/learn')->assertSee('data-auto-show="catalogue"', false);
    }

    public function test_replaying_tips_resets_dismissals(): void
    {
        $this->post('/guide/steps/catalogue/dismiss');
        $this->post('/guide/disable');

        $this->post('/guide/reset')->assertRedirect();

        $this->get('/learn')->assertSee('data-auto-show="catalogue"', false);
    }

    public function test_unknown_steps_are_ignored(): void
    {
        $this->post('/guide/steps/not-a-step/dismiss')->assertNoContent();

        $this->assertSame([], json_decode(DB::table('user_guide_preferences')->value('dismissed_steps') ?? '[]', true));
    }

    public function test_project_page_explains_the_project_with_an_ai_summary_cached_once(): void
    {
        $project = ProjectTemplateModel::where('title', 'like', 'PantryLink%')->firstOrFail();

        $this->get("/learn/{$project->id}")->assertOk()
            ->assertSee('About this project')
            ->assertSee('You will help a food bank stop wasting food.')
            ->assertSee('Sprint 1 — Logging Donations')
            ->assertSee("What you'll do", false)
            ->assertSee('Before you apply');

        $this->get("/learn/{$project->id}")->assertOk();
        $this->assertSame(1, $this->aiCalls, 'The AI summary should be generated once and cached');
    }

    public function test_project_explainer_falls_back_when_ai_is_unavailable(): void
    {
        Cache::flush();
        $this->aiFails = true;
        $project = ProjectTemplateModel::where('title', 'like', 'CampusBook%')->firstOrFail();

        $this->get("/learn/{$project->id}")->assertOk()
            ->assertSee('CampusBook is a beginner project.')
            ->assertSee('Sprint 2 — No More Double Bookings');
    }
}
