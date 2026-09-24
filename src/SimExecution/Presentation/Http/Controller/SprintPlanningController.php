<?php
namespace Src\SimExecution\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Command\ConfirmSprint\ConfirmSprintCommand;
use Src\SimExecution\Application\Command\EnsureSprintPlanningReady\EnsureSprintPlanningReadyCommand;
use Src\SimExecution\Application\Command\MoveItemToSprint\MoveItemToSprintCommand;
use Src\SimExecution\Application\Command\ReturnItemToBacklog\ReturnItemToBacklogCommand;
use Src\SimExecution\Application\Command\WriteSprintGoal\WriteSprintGoalCommand;
use Src\SimExecution\Application\Query\GetSprintPlanning\GetSprintPlanningQuery;
use Src\SimExecution\Domain\Exceptions\BacklogItemNotFoundException;
use Src\SimExecution\Domain\Exceptions\InvalidSessionTransitionException;
use Src\SimExecution\Domain\Exceptions\InvalidSprintTransitionException;
use Src\SimExecution\Domain\Exceptions\LearnerNotFoundException;
use Src\SimExecution\Domain\Exceptions\SessionNotFoundException;
use Src\SimExecution\Domain\Exceptions\SprintGoalRequiredException;
use Src\SimExecution\Domain\Exceptions\SprintNotFoundException;
use Src\SimExecution\Presentation\Http\Request\WriteSprintGoalRequest;

class SprintPlanningController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    public function show(string $project): View|RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new EnsureSprintPlanningReadyCommand(Auth::id(), $project));
        } catch (LearnerNotFoundException|SessionNotFoundException $e) {
            abort(404);
        } catch (InvalidSessionTransitionException $e) {
            return redirect()->route('learn.onboarding', $project)
                ->with('info', 'Complete induction before planning a sprint.');
        }

        $planning = $this->queryBus->ask(new GetSprintPlanningQuery(Auth::id(), $project));
        if ($planning === null) {
            // No sprint currently in planning — an active sprint means the board is next.
            return redirect()->route('learn.sprint-board', $project);
        }

        return view('learn.sprint-planning', ['project' => $project, 'planning' => $planning]);
    }

    public function writeGoal(WriteSprintGoalRequest $request, string $project): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new WriteSprintGoalCommand(
                userId:   Auth::id(),
                sprintId: $request->input('sprint_id'),
                goal:     $request->validated('goal'),
            ));
        } catch (LearnerNotFoundException|SprintNotFoundException $e) {
            abort(404);
        } catch (InvalidSprintTransitionException $e) {
            return back()->with('error', ucfirst($e->getMessage()) . '.');
        }

        return redirect()->route('learn.sprint-planning', $project)->with('success', 'Sprint goal saved.');
    }

    public function moveItem(Request $request, string $project, string $item): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new MoveItemToSprintCommand(
                userId:   Auth::id(),
                itemId:   $item,
                sprintId: $request->input('sprint_id'),
            ));
        } catch (LearnerNotFoundException|BacklogItemNotFoundException|SprintNotFoundException $e) {
            abort(404);
        } catch (InvalidSprintTransitionException $e) {
            return back()->with('error', ucfirst($e->getMessage()) . '.');
        }

        return redirect()->route('learn.sprint-planning', $project);
    }

    public function returnItem(string $project, string $item): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new ReturnItemToBacklogCommand(
                userId: Auth::id(),
                itemId: $item,
            ));
        } catch (LearnerNotFoundException|BacklogItemNotFoundException $e) {
            abort(404);
        }

        return redirect()->route('learn.sprint-planning', $project);
    }

    public function confirm(Request $request, string $project): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new ConfirmSprintCommand(
                userId:   Auth::id(),
                sprintId: $request->input('sprint_id'),
            ));
        } catch (LearnerNotFoundException|SprintNotFoundException|SessionNotFoundException $e) {
            abort(404);
        } catch (SprintGoalRequiredException $e) {
            return back()->withErrors(['goal' => ucfirst($e->getMessage()) . '.']);
        } catch (InvalidSprintTransitionException $e) {
            return back()->with('error', ucfirst($e->getMessage()) . '.');
        }

        return redirect()->route('learn.sprint-board', $project)
            ->with('success', 'Sprint confirmed. Time to get to work.');
    }
}
