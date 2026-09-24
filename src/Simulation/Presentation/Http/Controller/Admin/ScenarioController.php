<?php
namespace Src\Simulation\Presentation\Http\Controller\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\Simulation\Application\Command\AddReferenceMaterial\AddReferenceMaterialCommand;
use Src\Simulation\Application\Command\CreateScenario\CreateScenarioCommand;
use Src\Simulation\Application\Command\PublishScenario\PublishScenarioCommand;
use Src\Simulation\Application\Command\RemoveReferenceMaterial\RemoveReferenceMaterialCommand;
use Src\Simulation\Application\Command\UpdateScenario\UpdateScenarioCommand;
use Src\Simulation\Application\Query\GetProject\GetProjectQuery;
use Src\Simulation\Application\Query\GetScenario\GetScenarioQuery;
use Src\Simulation\Application\Query\ListScenariosByProject\ListScenariosByProjectQuery;
use Src\Simulation\Presentation\Http\Request\AddReferenceMaterialRequest;
use Src\Simulation\Presentation\Http\Request\StoreScenarioRequest;
use Src\Simulation\Presentation\Http\Request\UpdateScenarioRequest;

class ScenarioController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    public function index(string $project): View
    {
        $projectTemplate = $this->queryBus->ask(new GetProjectQuery($project));
        abort_if($projectTemplate === null, 404);

        $scenarios = $this->queryBus->ask(new ListScenariosByProjectQuery($project));

        return view('admin.scenarios.index', [
            'project'   => $projectTemplate,
            'scenarios' => $scenarios,
        ]);
    }

    public function create(string $project): View
    {
        $projectTemplate = $this->queryBus->ask(new GetProjectQuery($project));
        abort_if($projectTemplate === null, 404);

        return view('admin.scenarios.create', ['project' => $projectTemplate]);
    }

    public function store(StoreScenarioRequest $request, string $project): RedirectResponse
    {
        $this->commandBus->dispatch(new CreateScenarioCommand(
            projectId:            $project,
            title:                $request->string('title')->toString(),
            narrativeContext:     $request->string('narrative_context')->toString(),
            situationTrigger:     $request->string('situation_trigger')->toString(),
            situationTriggerType: $request->string('situation_trigger_type')->toString(),
            defaultAutonomyLevel: $request->string('default_autonomy_level')->toString(),
            learnerRoleLabel:     $request->string('learner_role_label')->toString(),
            isDiagnostic:         $request->boolean('is_diagnostic'),
        ));

        return redirect()->route('admin.projects.scenarios.index', $project)
            ->with('success', 'Scenario created.');
    }

    public function edit(string $project, string $id): View
    {
        $projectTemplate = $this->queryBus->ask(new GetProjectQuery($project));
        abort_if($projectTemplate === null, 404);

        $scenario = $this->queryBus->ask(new GetScenarioQuery($id));
        abort_if($scenario === null, 404);

        return view('admin.scenarios.edit', [
            'project'  => $projectTemplate,
            'scenario' => $scenario,
        ]);
    }

    public function update(UpdateScenarioRequest $request, string $project, string $id): RedirectResponse
    {
        $this->commandBus->dispatch(new UpdateScenarioCommand(
            scenarioId: $id,
            title:      $request->string('title')->toString(),
        ));

        return redirect()->route('admin.projects.scenarios.index', $project)
            ->with('success', 'Scenario updated.');
    }

    public function publish(string $project, string $id): View
    {
        // Toggle: read current state, flip it (matches ProjectController::publish()).
        $scenario = $this->queryBus->ask(new GetScenarioQuery($id));
        abort_if($scenario === null, 404);

        $this->commandBus->dispatch(new PublishScenarioCommand($id, ! $scenario->isPublished()));

        // HTMX expects the updated row fragment back.
        $scenario = $this->queryBus->ask(new GetScenarioQuery($id));
        return view('admin.scenarios._row', ['scenario' => $scenario, 'projectId' => $project]);
    }

    public function addMaterial(AddReferenceMaterialRequest $request, string $id): RedirectResponse
    {
        $this->commandBus->dispatch(new AddReferenceMaterialCommand(
            scenarioId:   $id,
            materialType: $request->string('material_type')->toString(),
            title:        $request->string('material_title')->toString(),
            content:      $request->string('content')->toString(),
            displayOrder: (int) $request->input('display_order', 0),
        ));

        return redirect()->back()->with('success', 'Reference material added.');
    }

    public function removeMaterial(string $id, string $materialId): RedirectResponse
    {
        $this->commandBus->dispatch(new RemoveReferenceMaterialCommand($id, $materialId));

        return redirect()->back()->with('success', 'Reference material removed.');
    }
}
