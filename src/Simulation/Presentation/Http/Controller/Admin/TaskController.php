<?php
namespace Src\Simulation\Presentation\Http\Controller\Admin;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\Simulation\Application\Command\AddCacVariant\AddCacVariantCommand;
use Src\Simulation\Application\Command\AddDependency\AddDependencyCommand;
use Src\Simulation\Application\Command\AddExpectedDeliverable\AddExpectedDeliverableCommand;
use Src\Simulation\Application\Command\AddGuidancePrompt\AddGuidancePromptCommand;
use Src\Simulation\Application\Command\AddKnowledgeAnchor\AddKnowledgeAnchorCommand;
use Src\Simulation\Application\Command\CreateTask\CreateTaskCommand;
use Src\Simulation\Application\Command\PublishTask\PublishTaskCommand;
use Src\Simulation\Application\Command\RemoveTaskChild\RemoveTaskChildCommand;
use Src\Simulation\Application\Command\UpdateTask\UpdateTaskCommand;
use Src\Simulation\Application\Query\GetTask\GetTaskQuery;
use Src\Simulation\Application\Query\ListTasksByScenario\ListTasksByScenarioQuery;
use Src\Simulation\Presentation\Http\Request\AddCacVariantRequest;
use Src\Simulation\Presentation\Http\Request\AddDependencyRequest;
use Src\Simulation\Presentation\Http\Request\AddExpectedDeliverableRequest;
use Src\Simulation\Presentation\Http\Request\AddGuidancePromptRequest;
use Src\Simulation\Presentation\Http\Request\AddKnowledgeAnchorRequest;
use Src\Simulation\Presentation\Http\Request\StoreTaskRequest;
use Src\Simulation\Presentation\Http\Request\UpdateTaskRequest;

class TaskController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus   $queryBus,
    ) {}

    public function index(string $scenario): View
    {
        $tasks = $this->queryBus->ask(new ListTasksByScenarioQuery($scenario));
        return view('admin.tasks.index', ['tasks' => $tasks, 'scenarioId' => $scenario]);
    }

    public function create(string $scenario): View
    {
        return view('admin.tasks.create', ['scenarioId' => $scenario]);
    }

    public function store(StoreTaskRequest $request, string $scenario): RedirectResponse
    {
        $this->commandBus->dispatch(new CreateTaskCommand(
            scenarioId:          $scenario,
            title:               $request->string('title')->toString(),
            taskBrief:           $request->string('task_brief')->toString(),
            taskType:            $request->string('task_type')->toString(),
            isCacRuntimeSet:     (bool) $request->input('is_cac_runtime_set'),
            isArchitectural:     (bool) $request->input('is_architectural'),
            planningLayerActive: (bool) $request->input('planning_layer_active'),
            domain:              $request->input('domain'),
            roleTags:            $request->input('role_tags', []),
            tools:               $request->input('tools', []),
            fixedComplexity:     $request->input('fixed_complexity'),
            fixedAutonomy:       $request->input('fixed_autonomy'),
            fixedContextFidelity:$request->input('fixed_context_fidelity'),
            modelResponseSummary:$request->input('model_response_summary'),
            timeLimitMinutes:    $request->input('time_limit_minutes'),
        ));

        return redirect()->route('admin.scenarios.tasks.index', $scenario)
            ->with('success', 'Task created.');
    }

    public function edit(string $scenario, string $id): View
    {
        $task = $this->queryBus->ask(new GetTaskQuery($id));
        abort_if($task === null, 404);
        return view('admin.tasks.edit', ['task' => $task, 'scenarioId' => $scenario]);
    }

    public function update(UpdateTaskRequest $request, string $scenario, string $id): RedirectResponse
    {
        $this->commandBus->dispatch(new UpdateTaskCommand(
            taskId:    $id,
            title:     $request->string('title')->toString(),
            taskBrief: $request->string('task_brief')->toString(),
        ));

        return redirect()->route('admin.scenarios.tasks.index', $scenario)
            ->with('success', 'Task updated.');
    }

    public function publish(string $scenario, string $id): View
    {
        $task = $this->queryBus->ask(new GetTaskQuery($id));
        abort_if($task === null, 404);

        $this->commandBus->dispatch(new PublishTaskCommand($id, ! $task->isPublished()));

        // HTMX expects the updated row fragment back.
        $task = $this->queryBus->ask(new GetTaskQuery($id));
        return view('admin.tasks._row', ['task' => $task, 'scenarioId' => $scenario]);
    }

    // --- HTMX child endpoints ---

    public function addDeliverable(AddExpectedDeliverableRequest $request, string $id): View
    {
        $this->commandBus->dispatch(new AddExpectedDeliverableCommand(
            taskId:       $id,
            type:         $request->string('type')->toString(),
            label:        $request->string('label')->toString(),
            description:  $request->input('description'),
            isRequired:   (bool) $request->input('is_required', true),
            displayOrder: (int) $request->input('display_order', 0),
        ));

        $task = $this->queryBus->ask(new GetTaskQuery($id));
        return view('admin.tasks._deliverable_row', ['deliverable' => last($task->expectedDeliverables()), 'taskId' => $id]);
    }

    public function addCacVariant(AddCacVariantRequest $request, string $id): View
    {
        try {
            $this->commandBus->dispatch(new AddCacVariantCommand(
                taskId:              $id,
                complexityLevel:     $request->string('complexity_level')->toString(),
                scenarioText:        $request->string('scenario_text')->toString(),
                scaffoldingTextLow:  $request->input('scaffolding_text_low'),
                scaffoldingTextMid:  $request->input('scaffolding_text_mid'),
                scaffoldingTextHigh: $request->input('scaffolding_text_high'),
                contextTextLow:      $request->input('context_text_low'),
                contextTextMid:      $request->input('context_text_mid'),
                contextTextHigh:     $request->input('context_text_high'),
            ));
        } catch (UniqueConstraintViolationException) {
            // Surface the unique(task_id, complexity_level) violation as a friendly error.
            return view('admin.tasks._cac_variant_error', [
                'message' => 'A variant for that complexity level already exists.',
            ]);
        }

        $task = $this->queryBus->ask(new GetTaskQuery($id));
        return view('admin.tasks._cac_variant_row', ['variant' => last($task->cacVariants()), 'taskId' => $id]);
    }

    public function addDependency(AddDependencyRequest $request, string $id): View
    {
        $this->commandBus->dispatch(new AddDependencyCommand(
            taskId:             $id,
            prerequisiteTaskId: $request->string('prerequisite_task_id')->toString(),
        ));

        $task = $this->queryBus->ask(new GetTaskQuery($id));
        return view('admin.tasks._dependency_row', ['dependency' => last($task->dependencies()), 'taskId' => $id]);
    }

    public function addKnowledgeAnchor(AddKnowledgeAnchorRequest $request, string $id): View
    {
        $this->commandBus->dispatch(new AddKnowledgeAnchorCommand(
            taskId:                 $id,
            conceptName:            $request->string('concept_name')->toString(),
            isRequired:             (bool) $request->input('is_required', false),
            conceptId:              $request->input('concept_id'),
            domain:                 $request->input('domain'),
            applicationExpectation: $request->input('application_expectation'),
            remediationHint:        $request->input('remediation_hint'),
        ));

        $task = $this->queryBus->ask(new GetTaskQuery($id));
        return view('admin.tasks._anchor_row', ['anchor' => last($task->knowledgeAnchors()), 'taskId' => $id]);
    }

    public function addGuidancePrompt(AddGuidancePromptRequest $request, string $id): View
    {
        $this->commandBus->dispatch(new AddGuidancePromptCommand(
            taskId:              $id,
            triggerDimension:    $request->string('trigger_dimension')->toString(),
            promptText:          $request->string('prompt_text')->toString(),
            deliveryMode:        $request->string('delivery_mode')->toString(),
            autonomyLevelFilter: $request->input('autonomy_level_filter'),
            displayOrder:        (int) $request->input('display_order', 0),
        ));

        $task = $this->queryBus->ask(new GetTaskQuery($id));
        return view('admin.tasks._prompt_row', ['prompt' => last($task->guidancePrompts()), 'taskId' => $id]);
    }

    public function removeChild(string $id, string $collection, string $childId): \Illuminate\Http\Response
    {
        $this->commandBus->dispatch(new RemoveTaskChildCommand(
            taskId:     $id,
            collection: $collection,
            childId:    $childId,
        ));

        // HTMX hx-swap="outerHTML" with an empty response removes the row.
        return response('', 200);
    }
}
