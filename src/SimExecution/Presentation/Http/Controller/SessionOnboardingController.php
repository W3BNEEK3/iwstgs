<?php
namespace Src\SimExecution\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Command\CompleteInduction\CompleteInductionCommand;
use Src\SimExecution\Application\Query\GetSessionOnboarding\GetSessionOnboardingQuery;
use Src\SimExecution\Domain\Exceptions\InvalidSessionTransitionException;
use Src\SimExecution\Domain\Exceptions\LearnerNotFoundException;
use Src\SimExecution\Domain\Exceptions\SessionNotFoundException;
use Src\Simulation\Application\Query\GetProject\GetProjectQuery;

class SessionOnboardingController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    public function show(string $project): View
    {
        $onboarding = $this->queryBus->ask(new GetSessionOnboardingQuery(
            userId:    Auth::id(),
            projectId: $project,
        ));

        abort_if($onboarding === null, 404);

        return view('learn.onboarding', ['project' => $project, 'onboarding' => $onboarding]);
    }

    public function complete(string $project): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new CompleteInductionCommand(
                userId:    Auth::id(),
                projectId: $project,
            ));
        } catch (LearnerNotFoundException|SessionNotFoundException $e) {
            abort(404);
        } catch (InvalidSessionTransitionException $e) {
            return redirect()->route($this->isBuild($project) ? 'learn.build' : 'learn.sprint-planning', $project)
                ->with('info', 'Induction is already complete.');
        }

        // Build projects have no sprint planning: straight to the milestone path.
        if ($this->isBuild($project)) {
            return redirect()->route('learn.build', $project)->with('success', 'Welcome aboard. Set up your repository, then start the first milestone.');
        }

        return redirect()->route('learn.sprint-planning', $project)
            ->with('success', 'Induction complete. Let\'s plan your first sprint.');
    }

    private function isBuild(string $projectId): bool
    {
        return (bool) $this->queryBus->ask(new GetProjectQuery($projectId))?->isBuildTrack();
    }
}
