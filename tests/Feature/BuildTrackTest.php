<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\AIMediation\Domain\Provider\AiEvaluatorClient;
use Src\Identity\Infrastructure\Persistence\Eloquent\Model\UserModel;
use Src\SourceControl\Domain\RepositoryHost;
use Src\SourceControl\Infrastructure\Fake\FakeRepositoryHost;
use Src\SourceControl\Infrastructure\GitHub\GitHubAppAuth;
use Src\Submission\Domain\Submission\AcceptanceResult;
use Tests\Fixtures\BuildProjectFixture;
use Tests\TestCase;

/** v2-1: Build-track projects built in the learner's own GitHub repository (design doc v2-01), against a fake GitHub. */
class BuildTrackTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private const REPO = 'ada/tasklet';
    private const GITHUB_USER = 4242;
    private const SECRET = 'webhook-test-secret';

    public string $tier = 'proficient';
    public ?string $lastPrompt = null;

    private FakeRepositoryHost $host;
    private BuildProjectFixture $fixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        DB::table('feature_flags')->where('flag_key', '!=', 'submission.code_execution')->update(['is_enabled' => true]);
        DB::table('feature_flags')->where('flag_key', 'simulation.diagnostic_assessment')->update(['is_enabled' => false]);
        config(['services.github.webhook_secret' => self::SECRET]);

        $this->host = new FakeRepositoryHost();
        $this->app->instance(RepositoryHost::class, $this->host);

        $this->app->instance(AiEvaluatorClient::class, new class($this) implements AiEvaluatorClient {
            public function __construct(private BuildTrackTest $test) {}

            public function evaluate(string $systemPrompt, array $contentBlocks): array
            {
                $this->test->lastPrompt = $contentBlocks[0]['text'] ?? '';
                $pass = in_array($this->test->tier, ['proficient', 'distinguished'], true);

                return [
                    'overall_tier'     => $this->test->tier,
                    'passes_threshold' => $pass,
                    'gap_type'         => $pass ? null : 'knowledge_gap',
                    'is_uncertain'     => false,
                    'dimensions'       => [
                        ['dimension_id' => 'dim_implementation', 'task_dimension_label' => 'Working feature', 'tier_achieved' => $this->test->tier,
                         'criteria_met' => [], 'criteria_missed' => $pass ? [] : ['The feature works as described.'], 'evaluator_notes' => 'Clear, small change.'],
                    ],
                ];
            }
        });

        $this->fixture = BuildProjectFixture::create();
        $this->actingAs(UserModel::where('email', 'learner@example.com')->firstOrFail());
        $this->post('/learn/enrol', ['entry_category' => 'inexperienced']);
    }

    // ---- Catalogue and enrolment ----------------------------------------------------

    public function test_build_projects_only_show_while_the_track_is_switched_on(): void
    {
        $this->get('/learn')->assertOk()->assertSee('Tasklet')->assertSee('Express + EJS')->assertSee('Challenges');

        DB::table('feature_flags')->where('flag_key', 'tracks.build')->update(['is_enabled' => false]);
        cache()->flush();

        $this->get('/learn')->assertOk()->assertDontSee('Tasklet');
        $this->get("/learn/{$this->fixture->project->id}")->assertNotFound();
        $this->post("/learn/{$this->fixture->project->id}/enrol", ['stack_variant_id' => $this->fixture->variantId]);
        $this->assertDatabaseMissing('learner_sessions', ['project_id' => $this->fixture->project->id]);
    }

    public function test_learners_choose_a_stack_and_harder_stacks_are_rank_gated(): void
    {
        $this->get("/learn/{$this->fixture->project->id}")->assertOk()
            ->assertSee('Choose your stack')->assertSee('Express API + React')->assertSee('Unlocks at Mid-1');

        $this->post("/learn/{$this->fixture->project->id}/enrol", ['stack_variant_id' => $this->fixture->hardVariantId])
            ->assertSessionHasErrors('stack_variant_id');
        $this->assertDatabaseMissing('learner_sessions', ['project_id' => $this->fixture->project->id]);

        $sessionId = $this->startBuild();
        $this->assertDatabaseHas('learner_sessions', ['id' => $sessionId, 'stack_variant_id' => $this->fixture->variantId, 'status' => 'active']);
        $roleId = DB::table('role_enrolments')->where('project_id', $this->fixture->project->id)->value('role_id');
        $this->assertSame('Solo Developer', DB::table('role_definitions')->where('id', $roleId)->value('title'));

        // No sprints on a Build project: planning sends you to the build, and none is created.
        $this->get("/learn/{$this->fixture->project->id}/sprint-planning")->assertRedirect("/learn/{$this->fixture->project->id}/build");
        $this->assertDatabaseMissing('learner_sprints', ['learner_session_id' => $sessionId]);
    }

    // ---- GitHub connection and repository linking -------------------------------------

    public function test_connect_github_and_link_the_repository_made_from_the_template(): void
    {
        $sessionId = $this->startBuild();
        $this->get("/learn/{$this->fixture->project->id}/build")->assertOk()
            ->assertSee('Connect GitHub')->assertSee('Create my repo')
            ->assertSee(BuildProjectFixture::TEMPLATE . '/generate?name=tasklet', false)
            ->assertSee('Anything that shows my list on my phone would be amazing!');

        $this->connectGitHub();
        $this->assertDatabaseHas('github_connections', ['github_user_id' => self::GITHUB_USER, 'github_login' => 'ada']);

        // A repo that wasn't generated from the template is not linked, and the learner is told why.
        $this->host->addRepository(self::GITHUB_USER, 'ada/tasklet', null);
        $this->post('/github/check')->assertSessionHas('info', fn ($m) => str_contains($m, 'wasn\'t created from the project template'));
        $this->assertDatabaseMissing('learner_repositories', ['learner_session_id' => $sessionId]);

        $this->host->repos = [];
        $this->linkRepository();
        $this->assertDatabaseHas('learner_repositories', [
            'learner_session_id' => $sessionId, 'full_name' => self::REPO, 'status' => 'linked', 'start_sha' => 'start000',
        ]);
        $this->get("/learn/{$this->fixture->project->id}/build")->assertOk()->assertSee(self::REPO)->assertDontSee('Create my repo');
    }

    public function test_the_oauth_callback_rejects_a_mismatched_state(): void
    {
        $this->host->addUser('code-1', self::GITHUB_USER, 'ada');
        $this->get('/github/connect')->assertRedirect();

        $this->get('/github/callback?state=wrong&code=code-1')->assertSessionHas('error');
        $this->assertDatabaseMissing('github_connections', ['github_user_id' => self::GITHUB_USER]);
    }

    // ---- Milestones -----------------------------------------------------------------------

    public function test_a_milestone_waits_for_its_tests_then_is_reviewed_and_the_build_moves_on(): void
    {
        $sessionId = $this->startBuild();
        $this->connectGitHub();
        $this->linkRepository();

        // M1: CI still running when submitted → waits, no evaluation yet.
        $this->host->push(self::REPO, 'aaa1111', 'Show tasks')->setFile(self::REPO, 'aaa1111', '.github/workflows/areyna.yml', BuildProjectFixture::WORKFLOW)
            ->startRun(self::REPO, 'aaa1111');
        $this->get($this->milestoneUrl($sessionId, $this->fixture->m1->id))->assertOk()
            ->assertSee('Render the list from an array in views/index.ejs.')->assertSee('Show tasks');
        $this->submitMilestone($sessionId, $this->fixture->m1->id, 'aaa1111');

        $submission = DB::table('submission_packages')->where('task_id', $this->fixture->m1->id)->first();
        $this->assertSame(['commit', 'pending', 'start000'], [$submission->source, $submission->ci_status, $submission->base_sha]);
        $this->assertDatabaseMissing('evaluation_results', ['submission_id' => $submission->id]);
        $this->get($this->milestoneUrl($sessionId, $this->fixture->m1->id))->assertSee('Tests running');

        // The run finishes; GitHub's webhook resumes the submission.
        $this->host->finishRun(self::REPO, 'aaa1111', BuildProjectFixture::report(['T1.1', 'T1.2']));
        $this->webhook('workflow_run', ['action' => 'completed', 'repository' => ['id' => $this->host->repoId(self::REPO)],
            'workflow_run' => ['head_sha' => 'aaa1111']])->assertStatus(202);

        $this->assertDatabaseHas('submission_packages', ['id' => $submission->id, 'ci_status' => 'passed']);
        $this->assertDatabaseHas('evaluation_results', ['submission_id' => $submission->id, 'passes_threshold' => true]);
        $this->assertDatabaseHas('learner_repositories', ['learner_session_id' => $sessionId, 'last_accepted_sha' => 'aaa1111']);

        // What the reviewer saw: tests, the diff, but never secrets or lock files.
        $this->assertStringContainsString('T1.1 [passed]', $this->lastPrompt);
        $this->assertStringContainsString('src/server.js', $this->lastPrompt);
        $this->assertStringContainsString('Express + EJS', $this->lastPrompt);
        $this->assertStringNotContainsString('hunter2', $this->lastPrompt);
        $this->assertStringNotContainsString('+ lots', $this->lastPrompt);

        // The requirement change after M1 arrives, and M2 is now the current milestone.
        $this->get("/learn/{$this->fixture->project->id}/build")->assertOk()
            ->assertSee('Could I add tasks too, not just see them?')->assertSee('1 of 3 milestones passed');
        $this->get("/learn/submissions/{$submission->id}/evaluation")->assertOk()->assertSee('Milestone passed')->assertSee('Clear, small change.');

        // M2: CI already finished at submit time → evaluated at once, compared with M1's commit.
        $this->host->push(self::REPO, 'bbb2222', 'Add tasks')->setFile(self::REPO, 'bbb2222', '.github/workflows/areyna.yml', BuildProjectFixture::WORKFLOW)
            ->finishRun(self::REPO, 'bbb2222', BuildProjectFixture::report(['T1.1', 'T1.2', 'T2.1']));
        $this->submitMilestone($sessionId, $this->fixture->m2->id, 'bbb2222');

        $this->assertDatabaseHas('submission_packages', ['task_id' => $this->fixture->m2->id, 'base_sha' => 'aaa1111', 'ci_status' => 'passed']);
        $this->assertSame($this->fixture->chapterTwoId, DB::table('learner_sessions')->where('id', $sessionId)->value('current_scenario_id'),
            'Passing every milestone of a chapter starts the next one');
        $this->get("/learn/{$this->fixture->project->id}/build")->assertOk()->assertSee('I closed the tab and everything was gone!');
    }

    public function test_milestones_open_one_at_a_time(): void
    {
        $sessionId = $this->startBuild();
        $this->connectGitHub();
        $this->linkRepository();
        $this->host->push(self::REPO, 'bbb2222', 'Jump ahead');

        $this->submitMilestone($sessionId, $this->fixture->m2->id, 'bbb2222')
            ->assertSessionHasErrors(['submission' => 'This milestone isn\'t open yet: finish the current one first.']);
        $this->assertDatabaseMissing('submission_packages', ['task_id' => $this->fixture->m2->id]);
    }

    public function test_another_user_cannot_see_or_prompt_someone_elses_milestone(): void
    {
        $sessionId = $this->startBuild();

        // The seeded admin also holds the learner role, so this is the ownership check answering
        // "not found" (it never confirms another learner's session exists).
        $this->actingAs(UserModel::where('email', 'admin@example.com')->firstOrFail());
        $this->get($this->milestoneUrl($sessionId, $this->fixture->m1->id))->assertNotFound();
        $this->post($this->milestoneUrl($sessionId, $this->fixture->m1->id) . '/refresh')->assertNotFound();
    }

    public function test_failing_tests_fail_the_milestone_even_when_the_review_is_positive(): void
    {
        $sessionId = $this->startBuild();
        $this->connectGitHub();
        $this->linkRepository();

        $this->pushFinished('aaa1111', BuildProjectFixture::report(['T1.1'], ['T1.2']));
        $this->submitMilestone($sessionId, $this->fixture->m1->id, 'aaa1111');

        $submission = DB::table('submission_packages')->where('task_id', $this->fixture->m1->id)->first();
        $this->assertSame('failed', $submission->ci_status);
        $this->assertDatabaseHas('evaluation_results', ['submission_id' => $submission->id, 'passes_threshold' => false, 'gap_type' => 'strategy_gap']);
        $this->assertNull(DB::table('learner_repositories')->value('last_accepted_sha'));
        $this->get($this->milestoneUrl($sessionId, $this->fixture->m1->id))->assertOk()
            ->assertSee('1 of 2 required tests passed.')->assertSee('expected 4 items, found 3')->assertSee('When tests fail');
    }

    public function test_breaking_an_earlier_milestone_brings_the_regression_fix(): void
    {
        $sessionId = $this->startBuild();
        $this->connectGitHub();
        $this->linkRepository();
        $this->pushFinished('aaa1111', BuildProjectFixture::report(['T1.1', 'T1.2']));
        $this->submitMilestone($sessionId, $this->fixture->m1->id, 'aaa1111');

        $this->pushFinished('bbb2222', BuildProjectFixture::report(['T1.2', 'T2.1'], ['T1.1']));
        $this->submitMilestone($sessionId, $this->fixture->m2->id, 'bbb2222');

        $report = json_decode(DB::table('submission_packages')->where('task_id', $this->fixture->m2->id)->value('ci_report'), true);
        $this->assertSame(['T1.1'], $report['regressions']);
        $this->assertDatabaseHas('injected_task_cards', [
            'learner_session_id' => $sessionId, 'card_type' => 'consequence', 'injected_task_id' => $this->fixture->regressionFix->id,
        ]);
        $this->assertStringContainsString('REGRESSION', $this->lastPrompt);
        $this->get("/learn/{$this->fixture->project->id}/build")->assertOk()
            ->assertSee('To fix first')->assertSee('Something that used to work broke');

        // Failing again doesn't stack a second copy of the same fix.
        $this->pushFinished('ccc3333', BuildProjectFixture::report(['T1.2', 'T2.1'], ['T1.1']));
        $this->submitMilestone($sessionId, $this->fixture->m2->id, 'ccc3333');
        $this->assertSame(1, DB::table('injected_task_cards')->where('injected_task_id', $this->fixture->regressionFix->id)->count());
    }

    public function test_a_changed_test_workflow_is_not_trusted(): void
    {
        $sessionId = $this->startBuild();
        $this->connectGitHub();
        $this->linkRepository();

        $this->host->push(self::REPO, 'aaa1111', 'Skip the tests')
            ->setFile(self::REPO, 'aaa1111', '.github/workflows/areyna.yml', "name: everything passes\n")
            ->finishRun(self::REPO, 'aaa1111', BuildProjectFixture::report(['T1.1', 'T1.2']));
        $this->submitMilestone($sessionId, $this->fixture->m1->id, 'aaa1111');

        $this->assertDatabaseHas('submission_packages', ['task_id' => $this->fixture->m1->id, 'ci_status' => 'errored']);
        $this->assertDatabaseHas('evaluation_results', ['passes_threshold' => false]);
    }

    public function test_a_commit_whose_tests_never_run_is_reviewed_after_the_wait_but_cannot_pass(): void
    {
        $sessionId = $this->startBuild();
        $this->connectGitHub();
        $this->linkRepository();
        $this->host->push(self::REPO, 'aaa1111', 'No workflow');
        $this->submitMilestone($sessionId, $this->fixture->m1->id, 'aaa1111');
        $this->assertDatabaseHas('submission_packages', ['ci_status' => 'pending']);

        $this->travel(16)->minutes();
        $this->artisan('submissions:resume-pending')->assertSuccessful();

        $this->assertDatabaseHas('submission_packages', ['task_id' => $this->fixture->m1->id, 'ci_status' => 'not_run']);
        $this->assertDatabaseHas('evaluation_results', ['passes_threshold' => false]);
    }

    // ---- Webhooks ---------------------------------------------------------------------------

    public function test_webhooks_must_be_signed_are_processed_once_and_removal_revokes_access(): void
    {
        $sessionId = $this->startBuild();
        $this->connectGitHub();
        $this->linkRepository();

        $this->call('POST', '/github/webhook', [], [], [], [
            'HTTP_X_GITHUB_EVENT' => 'ping', 'HTTP_X_HUB_SIGNATURE_256' => 'sha256=nope', 'CONTENT_TYPE' => 'application/json',
        ], '{}')->assertStatus(401);

        $payload = ['action' => 'removed', 'installation' => ['id' => 1], 'repositories_removed' => [['id' => $this->host->repoId(self::REPO)]]];
        $this->webhook('installation_repositories', $payload, 'delivery-1')->assertStatus(202);
        $this->webhook('installation_repositories', $payload, 'delivery-1')->assertOk(); // redelivery: acknowledged, not reprocessed
        $this->assertSame(1, DB::table('github_webhook_deliveries')->count());
        $this->assertDatabaseHas('learner_repositories', ['learner_session_id' => $sessionId, 'status' => 'access_lost']);

        $this->host->push(self::REPO, 'aaa1111', 'Work');
        $this->submitMilestone($sessionId, $this->fixture->m1->id, 'aaa1111')
            ->assertSessionHasErrors(['submission' => 'Link your GitHub repository first.']);
        $this->get("/learn/{$this->fixture->project->id}/build")->assertSee('Areyna lost access to ' . self::REPO);
    }

    // ---- Units ------------------------------------------------------------------------------

    public function test_acceptance_results_require_every_earlier_test_too(): void
    {
        $result = AcceptanceResult::fromReport(BuildProjectFixture::report(['T2.1']), ['T2.1'], ['T1.1'], 'failure', true);
        $this->assertSame('failed', $result->status);
        $this->assertSame(['T1.1'], $result->regressions, 'A required earlier test missing from the report counts as failed');

        $this->assertSame('failed', AcceptanceResult::fromReport(BuildProjectFixture::report(['T1.1'], [], false), ['T1.1'], [], 'failure', true)->status);
        $this->assertSame('errored', AcceptanceResult::fromReport(null, ['T1.1'], [], 'failure', true)->status);
        $this->assertSame('errored', AcceptanceResult::fromReport(BuildProjectFixture::report(['T1.1']), ['T1.1'], [], 'success', false)->status);
        $this->assertSame('passed', AcceptanceResult::fromReport(BuildProjectFixture::report(['T1.1', 'T2.1']), ['T2.1'], ['T1.1'], 'success', true)->status);
    }

    public function test_the_github_app_signs_a_verifiable_jwt(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);
        $path = tempnam(sys_get_temp_dir(), 'gh-app-key');
        file_put_contents($path, $pem);

        try {
            $auth = new GitHubAppAuth(['app_id' => '12345', 'private_key_path' => $path]);
            $this->assertTrue($auth->isConfigured());

            [$header, $payload, $signature] = explode('.', $auth->appJwt());
            $decode = fn (string $s) => base64_decode(strtr($s, '-_', '+/'));
            $claims = json_decode($decode($payload), true);
            $this->assertSame('12345', $claims['iss']);
            $this->assertLessThanOrEqual(600, $claims['exp'] - $claims['iat']);
            $this->assertSame(1, openssl_verify("{$header}.{$payload}", $decode($signature), openssl_pkey_get_details($key)['key'], OPENSSL_ALGO_SHA256));
        } finally {
            @unlink($path);
        }

        $this->assertFalse((new GitHubAppAuth(['app_id' => '1', 'private_key_path' => '/nonexistent.pem']))->isConfigured());
    }

    // ---- Helpers ------------------------------------------------------------------------------

    private function startBuild(): string
    {
        $projectId = $this->fixture->project->id;
        $this->post("/learn/{$projectId}/enrol", ['stack_variant_id' => $this->fixture->variantId])->assertSessionHasNoErrors();
        $this->post("/learn/{$projectId}/onboarding")->assertRedirect("/learn/{$projectId}/build");

        return DB::table('learner_sessions')->where('project_id', $projectId)->value('id');
    }

    private function connectGitHub(): void
    {
        $this->host->addUser('code-1', self::GITHUB_USER, 'ada');
        $this->get('/github/connect?return=/learn')->assertRedirectContains('github.com/login/oauth/authorize');
        $state = session('github.oauth_state');
        $this->get("/github/callback?state={$state}&code=code-1")->assertRedirect('/learn')->assertSessionHas('success');
    }

    private function linkRepository(): void
    {
        $this->host->addRepository(self::GITHUB_USER, self::REPO, BuildProjectFixture::TEMPLATE, 'start000');
        $this->post('/github/check')->assertSessionHas('success');
    }

    private function pushFinished(string $sha, array $report): void
    {
        $this->host->push(self::REPO, $sha, "Commit {$sha}")
            ->setFile(self::REPO, $sha, '.github/workflows/areyna.yml', BuildProjectFixture::WORKFLOW)
            ->finishRun(self::REPO, $sha, $report);
    }

    private function submitMilestone(string $sessionId, string $taskId, string $sha): TestResponse
    {
        $this->travel(1)->minutes();

        return $this->post($this->milestoneUrl($sessionId, $taskId), [
            'commit_sha'  => $sha,
            'explanation' => 'I rendered the tasks from an array, because it keeps data separate from markup. I checked it in the browser.',
        ]);
    }

    private function milestoneUrl(string $sessionId, string $taskId): string
    {
        return "/learn/sessions/{$sessionId}/milestones/{$taskId}";
    }

    private function webhook(string $event, array $payload, ?string $delivery = null): TestResponse
    {
        $body = json_encode($payload);

        return $this->call('POST', '/github/webhook', [], [], [], [
            'HTTP_X_GITHUB_EVENT'      => $event,
            'HTTP_X_GITHUB_DELIVERY'   => $delivery ?? uniqid('delivery-', true),
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=' . hash_hmac('sha256', $body, self::SECRET),
            'CONTENT_TYPE'             => 'application/json',
        ], $body);
    }
}
