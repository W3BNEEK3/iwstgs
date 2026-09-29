<?php
namespace Src\Guidance\Presentation\View;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;
use Illuminate\View\View;
use Src\Guidance\Application\Query\GetPageGuide\GetPageGuideQuery;
use Src\Guidance\Application\Query\GetPageGuide\PageGuideView;
use Src\Guidance\Application\Service\StuckGuideTrigger;
use Src\Guidance\Domain\GuideCatalog;
use Src\Shared\Application\Bus\QueryBus;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;

/**
 * <x-guide :context="[...]" /> — rendered once per page by the layout shells.
 * Carries this page's authored walkthrough (inferred from the route; views
 * report milestone flags through $guideContext) and switches on Tiroco's own
 * messages, which the card fetches after the page has loaded so they never
 * slow a page down.
 */
class GuideComponent extends Component
{
    public ?PageGuideView $guide = null;
    public ?string $page = null;
    public bool $messagesOn = false;

    /** @param array<string, bool> $context */
    public function __construct(QueryBus $queryBus, FeatureFlagService $flags, array $context = [])
    {
        if (! Auth::check()) {
            return;
        }

        $this->page = GuideCatalog::ROUTE_PAGES[Route::currentRouteName()] ?? null;
        if ($this->page !== null) {
            $this->guide = $queryBus->ask(new GetPageGuideQuery((string) Auth::id(), $this->page, $context));
        }

        $this->messagesOn = $flags->isEnabled('guide.ai_nudges') || $flags->isEnabled('guide.announcements');
    }

    public function shouldRender(): bool
    {
        return $this->guide !== null || $this->messagesOn;
    }

    /** On a task page the card reports a long stretch without submitting (the stuck-idle trigger). */
    public function stuckWatch(): ?array
    {
        $route = Route::current();
        if (! $this->messagesOn || $route?->getName() !== 'learn.task-submit') {
            return null;
        }

        return [
            'url'     => route('guide.stuck'),
            'session' => (string) $route->parameter('session'),
            'task'    => (string) $route->parameter('task'),
            'minutes' => StuckGuideTrigger::MIN_MINUTES,
        ];
    }

    public function render(): View
    {
        return view('guidance.assistant');
    }
}
