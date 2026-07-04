<?php
namespace Src\Simulation\Presentation\Http\Controller\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Src\Competency\Application\Query\ListCompetenceDimensions\ListCompetenceDimensionsQuery;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\Simulation\Application\Command\CreateRubricCriterion\CreateRubricCriterionCommand;
use Src\Simulation\Application\Command\RemoveRubricCriterion\RemoveRubricCriterionCommand;
use Src\Simulation\Application\Command\UpdateRubricCriterion\UpdateRubricCriterionCommand;
use Src\Simulation\Application\Query\GetCriterion\GetCriterionQuery;
use Src\Simulation\Application\Query\ListCriteriaByTask\ListCriteriaByTaskQuery;
use Src\Simulation\Domain\Exceptions\RubricSetMissingException;
use Src\Simulation\Presentation\Http\Request\StoreRubricCriterionRequest;
use Src\Simulation\Presentation\Http\Request\UpdateRubricCriterionRequest;

class RubricCriterionController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus   $queryBus,
    ) {}

    public function index(string $taskId): View
    {
        $criteria = $this->queryBus->ask(new ListCriteriaByTaskQuery($taskId));
        
        return view('admin.criteria.index', [
            'taskId' => $taskId,
            'criteria' => $criteria,
        ]);
    }

    public function create(string $taskId): View
    {
        $dimensions = $this->queryBus->ask(new ListCompetenceDimensionsQuery());
        return view('admin.criteria.create', [
            'taskId' => $taskId,
            'dimensions' => $dimensions,
        ]);
    }

    public function store(StoreRubricCriterionRequest $request, string $taskId): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new CreateRubricCriterionCommand(
                taskId: $taskId,
                taskDimensionLabel: $request->string('task_dimension_label')->toString(),
                parentDimensionId: $request->string('parent_dimension_id')->toString(),
                complexityLevel: $request->string('complexity_level')->toString(),
                criterionText: $request->string('criterion_text')->toString(),
                weight: $request->string('weight')->toString(),
                dimensionWeight: $request->string('dimension_weight')->toString(),
                claudeDetectionHint: $request->string('claude_detection_hint')->toString(),
                distinguishedDescription: $request->string('distinguished_description')->toString(),
                proficientDescription: $request->string('proficientDescription')->toString(),
                developingDescription: $request->string('developing_description')->toString(),
                beginningDescription: $request->string('beginning_description')->toString(),
                isArchitectural: (bool) $request->input('is_architectural'),
                isPlanningLayer: (bool) $request->input('is_planning_layer'),
                referenceDocAnchor: $request->input('reference_doc_anchor'),
            ));
        } catch (RubricSetMissingException $e) {
            return back()->withInput()->withErrors(['rubric_set' => $e->getMessage()]);
        }

        return redirect()->route('admin.tasks.criteria.index', $taskId)
            ->with('success', 'Criterion added successfully.');
    }

    public function edit(string $taskId, string $id): View
    {
        $criterion = $this->queryBus->ask(new GetCriterionQuery($id));
        abort_if($criterion === null, 404);

        return view('admin.criteria.edit', [
            'taskId' => $taskId,
            'criterion' => $criterion,
        ]);
    }

    public function update(UpdateRubricCriterionRequest $request, string $id): RedirectResponse
    {
        $this->commandBus->dispatch(new UpdateRubricCriterionCommand(
            criterionId: $id,
            criterionText: $request->string('criterion_text')->toString(),
            weight: $request->string('weight')->toString(),
            dimensionWeight: $request->string('dimension_weight')->toString(),
        ));

        // Note: the route parameters don't include taskId directly on update.
        // But we redirect back to the previous page or require taskId.
        // Assuming we pass taskId as a hidden field or query param, or just redirect back.
        return back()->with('success', 'Criterion updated.');
    }

    public function destroy(string $id): \Illuminate\Http\Response
    {
        $this->commandBus->dispatch(new RemoveRubricCriterionCommand($id));
        return response('', 200); // For HTMX row swap
    }
}
