<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Identity\Infrastructure\Persistence\Eloquent\Model\UserModel;
use Tests\TestCase;

class AiEngineConfigTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(UserModel::where('email', 'admin@example.com')->firstOrFail());
    }

    public function test_shows_both_providers_and_marks_the_active_one(): void
    {
        config([
            'services.ai_evaluation.provider' => 'Gemini',
            'services.gemini.key' => 'set', 'services.gemini.model' => 'gemini-test',
            'services.anthropic.key' => null, 'services.anthropic.model' => 'claude-test',
        ]);

        $this->get('/admin/ai-engine/config')->assertOk()
            ->assertSeeInOrder(['Claude (Anthropic)', 'Standby', 'claude-test', 'Not set — ANTHROPIC_API_KEY'])
            ->assertSeeInOrder(['Gemini (Google)', 'Active', 'gemini-test', 'Configured'])
            ->assertDontSee('CLAUDE_API_KEY');
    }

    public function test_warns_when_the_provider_value_is_not_recognised(): void
    {
        config(['services.ai_evaluation.provider' => 'openai']);

        $this->get('/admin/ai-engine/config')->assertOk()
            ->assertSee("isn't recognised, so Claude is being used", false);
    }
}
