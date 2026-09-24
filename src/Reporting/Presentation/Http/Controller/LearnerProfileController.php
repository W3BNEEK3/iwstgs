<?php
namespace Src\Reporting\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\Reporting\Application\Query\GetLearnerProfileDashboard\GetLearnerProfileDashboardQuery;
use Src\Reporting\Application\Query\GetQualificationReport\GetQualificationReportQuery;
use Src\Reporting\Application\Query\GetSessionSummary\GetSessionSummaryQuery;
use Src\Shared\Application\Bus\QueryBus;

class LearnerProfileController
{
    public function __construct(private readonly QueryBus $queryBus) {}

    public function show(): View|RedirectResponse
    {
        $dashboard = $this->queryBus->ask(new GetLearnerProfileDashboardQuery(Auth::id()));
        if ($dashboard === null) {
            return redirect()->route('learn.catalogue');
        }

        return view('learn.profile', ['dashboard' => $dashboard]);
    }

    public function qualification(): View|RedirectResponse
    {
        $results = $this->queryBus->ask(new GetQualificationReportQuery(Auth::id()));
        if ($results === null) {
            return redirect()->route('learn.catalogue');
        }

        return view('learn.profile-qualification', ['results' => $results]);
    }

    public function session(string $session): View|RedirectResponse
    {
        $summary = $this->queryBus->ask(new GetSessionSummaryQuery(Auth::id(), $session));
        if ($summary === null) {
            abort(404);
        }

        return view('learn.profile-session', ['summary' => $summary]);
    }
}
