<?php
namespace Src\SimExecution\Presentation\Http\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Query\GetProjectDetail\GetProjectDetailQuery;
use Src\Simulation\Application\Query\ListPublishedProjects\ListPublishedProjectsQuery;

class ProjectCatalogueController
{
    public function __construct(private readonly QueryBus $queryBus) {}

    public function index(): View
    {
        $projects = $this->queryBus->ask(new ListPublishedProjectsQuery());
        return view('learn.catalogue', ['projects' => $projects]);
    }

    public function show(string $project): View
    {
        $detail = $this->queryBus->ask(new GetProjectDetailQuery(
            projectId: $project,
            userId: Auth::id(),
        ));

        abort_if($detail === null, 404);

        return view('learn.catalogue-show', ['project' => $detail]);
    }
}
