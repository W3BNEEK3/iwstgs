<?php
namespace Src\SimExecution\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Src\Shared\Application\Bus\CommandBus;
use Src\SimExecution\Application\Command\EnrolInProject\EnrolInProjectCommand;
use Src\SimExecution\Domain\Exceptions\AlreadyEnrolledInProjectException;
use Src\SimExecution\Domain\Exceptions\InsufficientExperienceException;
use Src\SimExecution\Domain\Exceptions\LearnerNotFoundException;
use Src\SimExecution\Domain\Exceptions\ProjectNotAvailableException;
use Src\SimExecution\Domain\Exceptions\RoleNotAvailableForProjectException;
use Src\SimExecution\Presentation\Http\Request\EnrolInProjectRequest;

class ProjectEnrolmentController
{
    public function __construct(private readonly CommandBus $commandBus) {}

    public function store(EnrolInProjectRequest $request, string $project): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new EnrolInProjectCommand(
                userId: Auth::id(),
                projectId: $project,
                roleId: $request->input('role_id'),
            ));
        } catch (AlreadyEnrolledInProjectException $e) {
            return redirect()->route('learn.catalogue.show', $project)->with('info', ucfirst($e->getMessage()) . '.');
        } catch (InsufficientExperienceException|RoleNotAvailableForProjectException|ProjectNotAvailableException|LearnerNotFoundException $e) {
            return back()->withErrors(['role_id' => ucfirst($e->getMessage()) . '.']);
        }

        return redirect()->route('learn.catalogue.show', $project)
            ->with('success', 'You are enrolled. Your session has started.');
    }
}
