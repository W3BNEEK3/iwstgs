<?php
namespace Src\AIMediation\Presentation\Http\Controller\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Src\AIMediation\Application\Query\ListAiMediationEvents\ListAiMediationEventsQuery;
use Src\AIMediation\Application\Query\ListConceptTagRecommendations\ListConceptTagRecommendationsQuery;
use Src\AIMediation\Infrastructure\Persistence\Eloquent\Model\ConceptTagRecommendationModel;
use Src\Shared\Application\Bus\QueryBus;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;

/**
 * Tiroco/AI Engine admin section — Part 2 of the gap-fix plan.
 * Three sub-pages: Event Log, Concept Tag Recommendations, Config Display.
 */
final class AiEngineController extends Controller
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly FeatureFlagService $flags,
    ) {}

    /** GET /admin/ai-engine — paginated audit log of all AI decisions. */
    public function index(Request $request): \Illuminate\View\View
    {
        $filters = [
            'trigger_type' => $request->query('trigger_type'),
            'action_taken' => $request->query('action_taken'),
            'learner_id'   => $request->query('learner_id'),
            'date_from'    => $request->query('date_from'),
            'date_to'      => $request->query('date_to'),
        ];

        $events = $this->queryBus->ask(new ListAiMediationEventsQuery(
            filters:  array_filter($filters, fn ($v) => $v !== null && $v !== ''),
            perPage:  30,
        ));

        return view('admin.ai-engine.index', compact('events', 'filters'));
    }

    /** GET /admin/ai-engine/recommendations — pending concept tag suggestions from Claude. */
    public function recommendations(): \Illuminate\View\View
    {
        $recommendations = $this->queryBus->ask(new ListConceptTagRecommendationsQuery(status: 'pending'));

        return view('admin.ai-engine.recommendations', compact('recommendations'));
    }

    /** POST /admin/ai-engine/recommendations/{id}/approve — curator approves a concept suggestion. */
    public function approveRecommendation(string $id): RedirectResponse
    {
        ConceptTagRecommendationModel::where('id', $id)->update([
            'status'      => 'approved',
            'curator_id'  => auth()->id(),
            'resolved_at' => now(),
        ]);

        return redirect()->route('admin.ai-engine.recommendations')->with('success', 'Recommendation approved.');
    }

    /** POST /admin/ai-engine/recommendations/{id}/dismiss — curator dismisses a concept suggestion. */
    public function dismissRecommendation(Request $request, string $id): RedirectResponse
    {
        ConceptTagRecommendationModel::where('id', $id)->update([
            'status'       => 'dismissed',
            'curator_id'   => auth()->id(),
            'curator_notes' => $request->input('notes'),
            'resolved_at'  => now(),
        ]);

        return redirect()->route('admin.ai-engine.recommendations')->with('success', 'Recommendation dismissed.');
    }

    /** GET /admin/ai-engine/config — read-only display of AI configuration and thresholds. */
    public function config(): \Illuminate\View\View
    {
        $aiFlags = [
            'aimediation.claude_evaluation',
            'adaptive.consequence_tasks',
            'adaptive.suggestion_tasks',
            'adaptive.rank_management',
            'adaptive.scenario_transitions',
        ];

        $flagStates = collect($aiFlags)->mapWithKeys(fn ($key) => [
            $key => $this->flags->isEnabled($key),
        ])->all();

        $config = [
            'provider'             => config('services.claude.model', 'claude-3-5-sonnet-20241022'),
            'api_key_set'          => ! empty(config('services.claude.api_key')),
            'success_streak_threshold' => 3,
            'failure_streak_threshold' => 3,
            'habit_pattern_threshold'  => 3,
            'cac_priority_order'       => ['Autonomy', 'Context Fidelity', 'Complexity'],
            'pass_tiers'               => ['proficient', 'distinguished'],
        ];

        return view('admin.ai-engine.config', compact('flagStates', 'config'));
    }
}
