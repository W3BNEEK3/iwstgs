<?php
namespace Src\Reporting\Presentation\Http\Controller\Admin;

use Illuminate\View\View;
use Src\Reporting\Application\Query\ListLearnerOverviews\ListLearnerOverviewsQuery;
use Src\Reporting\Application\Query\ListTaskPerformance\ListTaskPerformanceQuery;
use Src\Shared\Application\Bus\QueryBus;

class AdminReportingController
{
    public function __construct(private readonly QueryBus $queryBus) {}

    public function learners(): View
    {
        $overviews = $this->queryBus->ask(new ListLearnerOverviewsQuery());
        return view('admin.reporting.learners', ['overviews' => $overviews]);
    }

    public function tasks(): View
    {
        $performance = $this->queryBus->ask(new ListTaskPerformanceQuery());
        return view('admin.reporting.tasks', ['performance' => $performance]);
    }
}
