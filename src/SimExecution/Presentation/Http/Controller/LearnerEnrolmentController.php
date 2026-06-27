<?php

namespace Src\SimExecution\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Src\SimExecution\Application\Command\EnrolAsLearner\EnrolAsLearnerCommand;
use Src\SimExecution\Domain\Enrollment\AlreadyEnrolledException;
use Src\SimExecution\Presentation\Http\Request\EnrolAsLearnerRequest;
use Src\Shared\Application\Bus\CommandBus;

class LearnerEnrolmentController
{
    public function __construct(private readonly CommandBus $commandBus) {}

    public function show(): View
    {
        // Show the enrolment form — entry_category and years_experience
        return view('learn.enrol');
    }

    public function store(EnrolAsLearnerRequest $request): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new EnrolAsLearnerCommand(
                userId:          auth()->id(),
                entryCategory:   $request->input('entry_category'),
                yearsExperience: $request->input('years_experience'),
            ));
        } catch (AlreadyEnrolledException $e) {
            return redirect()->route('learn.dashboard')
                ->with('info', 'You are already enrolled as a learner.');
        }

        // After enrolment, redirect to diagnostic (Phase 5)
        // For now redirect to a placeholder
        return redirect()->route('learn.enrol.success');
    }
}