<?php
namespace Src\SimExecution\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Command\SubmitSprint\SubmitSprintCommand;
use Src\SimExecution\Application\Command\UpdateBacklogItemStatus\UpdateBacklogItemStatusCommand;
use Src\SimExecution\Application\Query\GetSprintBoard\GetSprintBoardQuery;
use Src\SimExecution\Domain\Exceptions\BacklogItemNotFoundException;
use Src\SimExecution\Domain\Exceptions\InvalidBacklogItemTransitionException;
use Src\SimExecution\Domain\Exceptions\InvalidSprintTransitionException;
use Src\SimExecution\Domain\Exceptions\LearnerNotFoundException;
use Src\SimExecution\Domain\Exceptions\SprintNotFoundException;
use Src\SimExecution\Presentation\Http\Request\UpdateBacklogItemStatusRequest;

class SprintBoardController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    public function show(string $project): View|RedirectResponse
    {
        $board = $this->queryBus->ask(new GetSprintBoardQuery(Auth::id(), $project));
        if ($board === null) {
            return redirect()->route('learn.sprint-planning', $project);
        }

        return view('learn.sprint-board', ['project' => $project, 'board' => $board]);
    }

    public function updateItemStatus(UpdateBacklogItemStatusRequest $request, string $project, string $item): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new UpdateBacklogItemStatusCommand(
                userId:       Auth::id(),
                itemId:       $item,
                targetStatus: $request->validated('target_status'),
            ));
        } catch (LearnerNotFoundException|BacklogItemNotFoundException $e) {
            abort(404);
        } catch (InvalidBacklogItemTransitionException $e) {
            return back()->with('error', ucfirst($e->getMessage()) . '.');
        }

        return redirect()->route('learn.sprint-board', $project);
    }

    public function submit(Request $request, string $project): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new SubmitSprintCommand(
                userId:   Auth::id(),
                sprintId: $request->input('sprint_id'),
            ));
        } catch (LearnerNotFoundException|SprintNotFoundException $e) {
            abort(404);
        } catch (InvalidSprintTransitionException $e) {
            return back()->with('error', ucfirst($e->getMessage()) . '.');
        }

        return redirect()->route('learn.sprint-board', $project)
            ->with('success', 'Sprint submitted.');
    }
}
