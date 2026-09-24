<?php
namespace Src\SimExecution\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Src\SimExecution\Application\Command\EnrolAsLearner\EnrolAsLearnerCommand;
use Src\SimExecution\Domain\Exceptions\AlreadyEnrolledException;
use Src\SimExecution\Presentation\Http\Request\EnrolAsLearnerRequest;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;

class LearnerEnrolmentController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly FeatureFlagService $flags,
    ) {}

    public function show(): View
    {
        // Show the enrolment form — entry_category and years_experience
        return view('learn.enrol');
    }

    public function store(EnrolAsLearnerRequest $request): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new EnrolAsLearnerCommand(
                userId:          \Illuminate\Support\Facades\Auth::id(),
                entryCategory:   $request->input('entry_category'),
                yearsExperience: $request->input('years_experience'),
            ));
        } catch (AlreadyEnrolledException $e) {
            return redirect()->route('learn.catalogue')
                ->with('info', 'You are already enrolled as a learner.');
        }

        if ($this->flags->isEnabled('simulation.diagnostic_assessment')) {
            return redirect()->route('learn.diagnostic');
        }

        return redirect()->route('learn.catalogue')
            ->with('success', 'Welcome! Browse projects to get started.');
    }
}
