<?php
namespace Src\Guidance\Presentation\View;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;
use Illuminate\View\View;
use Src\Guidance\Application\Query\GetPageGuide\GetPageGuideQuery;
use Src\Guidance\Application\Query\GetPageGuide\PageGuideView;
use Src\Guidance\Domain\GuideCatalog;
use Src\Shared\Application\Bus\QueryBus;

/**
 * <x-guide :context="[...]" /> — rendered once per page by the layout shells.
 * The page is inferred from the current route; views report milestone flags
 * through $guideContext, which the shells pass in.
 */
class GuideComponent extends Component
{
    public ?PageGuideView $guide = null;

    /** @param array<string, bool> $context */
    public function __construct(QueryBus $queryBus, array $context = [])
    {
        $page = GuideCatalog::ROUTE_PAGES[Route::currentRouteName()] ?? null;
        if ($page !== null && Auth::check()) {
            $this->guide = $queryBus->ask(new GetPageGuideQuery((string) Auth::id(), $page, $context));
        }
    }

    public function shouldRender(): bool
    {
        return $this->guide !== null;
    }

    public function render(): View
    {
        return view('guidance.assistant');
    }
}
