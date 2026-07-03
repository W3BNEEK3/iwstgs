<?php
namespace Src\Simulation\Presentation\Http\Controller\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\Simulation\Application\Command\CreateProject\CreateProjectCommand;
use Src\Simulation\Application\Command\PublishProject\PublishProjectCommand;
use Src\Simulation\Application\Command\UpdateProject\UpdateProjectCommand;
use Src\Simulation\Application\Query\GetProject\GetProjectQuery;
use Src\Simulation\Application\Query\ListProjects\ListProjectsQuery;
use Src\Simulation\Presentation\Http\Request\StoreProjectRequest;
use Src\Simulation\Presentation\Http\Request\UpdateProjectRequest;

class ProjectController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    public function index(): View
    {
        $projects = $this->queryBus->ask(new ListProjectsQuery());
        return view('admin.projects.index', ['projects' => $projects]);
    }

    public function create(): View
    {
        return view('admin.projects.create');
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        try {
                $this->commandBus->dispatch(new CreateProjectCommand(
                title:              $request->string('title')->toString(),
                projectType:        $request->string('project_type')->toString(),
                businessContext:    $request->string('business_context')->toString(),
                specializationTags: $request->input('specialization_tags', []),
                difficultyLevel:    $request->string('difficulty_level')->toString(),
                tagline:            $request->input('tagline'),
                businessDomain:     $request->input('business_domain'),
            )); 
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return back()->withInput()->withErrors([
                'sequence_order' => 'That position is already taken in this project. Try again.',
            ]);
        }

        return redirect()->route('admin.projects.index')
            ->with('success', 'Project created.');
    }

    public function edit(string $id): View
    {
        $project = $this->queryBus->ask(new GetProjectQuery($id));
        abort_if($project === null, 404);
        return view('admin.projects.edit', ['project' => $project]);
    }

    public function update(UpdateProjectRequest $request, string $id): RedirectResponse
    {
        $this->commandBus->dispatch(new UpdateProjectCommand(
            projectId:       $id,
            title:           $request->string('title')->toString(),
            businessContext: $request->string('business_context')->toString(),
        ));

        return redirect()->route('admin.projects.index')->with('success', 'Project updated.');
    }

    public function publish(string $id): View
    {
        // Toggle: read current state, flip it.
        $project = $this->queryBus->ask(new GetProjectQuery($id));
        abort_if($project === null, 404);

        $this->commandBus->dispatch(new PublishProjectCommand($id, ! $project->isPublished()));

        // HTMX expects the updated row fragment back.
        $project = $this->queryBus->ask(new GetProjectQuery($id));
        return view('admin.projects._row', ['project' => $project]);
    }
}
