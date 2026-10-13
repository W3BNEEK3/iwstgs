<?php
namespace Src\Submission\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Query\GetBuildProgress\GetBuildProgressQuery;
use Src\SimExecution\Application\Query\GetLearnerSession\GetLearnerSessionQuery;
use Src\SimExecution\Application\Query\IsTaskInjectedForSession\IsTaskInjectedForSessionQuery;
use Src\Simulation\Application\Query\GetStackVariant\GetStackVariantQuery;
use Src\Simulation\Application\Query\GetTaskVariantSpec\GetTaskVariantSpecQuery;
use Src\SourceControl\Application\Query\GetLinkedRepository\GetLinkedRepositoryQuery;
use Src\SourceControl\Application\Query\ListRecentCommits\ListRecentCommitsQuery;
use Src\SourceControl\Domain\HostUnavailable;
use Src\Submission\Application\Command\ResumePendingSubmissions\ResumePendingSubmissionsCommand;
use Src\Submission\Application\Command\SubmitMilestone\SubmitMilestoneCommand;
use Src\Submission\Application\Query\GetSubmissionForm\GetSubmissionFormQuery;
use Src\Submission\Application\Query\ListAttempts\ListAttemptsQuery;
use Src\Submission\Domain\Exceptions\LearnerNotFoundException;
use Src\Submission\Domain\Exceptions\SubmissionNotAllowedException;

/**
 * A milestone (or an injected fix) on a Build project: the brief, the stack
 * notes, a commit picker and explanation box, and every attempt so far with
 * its test results. While tests are running the page refreshes itself.
 */
class MilestoneController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    public function show(string $session, string $task): View
    {
        $form = $this->queryBus->ask(new GetSubmissionFormQuery(Auth::id(), $session, $task));
        abort_if($form === null, 404);

        $sessionView = $this->queryBus->ask(new GetLearnerSessionQuery($session));
        abort_if($sessionView?->stackVariantId === null, 404);

        $progress = $this->queryBus->ask(new GetBuildProgressQuery($session));
        $isInjected = (bool) $this->queryBus->ask(new IsTaskInjectedForSessionQuery($session, $task));
        $isLocked = ! $isInjected && ! $progress->isCurrent($task);

        // Waiting submissions get another look every time the page loads (the page refreshes itself while waiting).
        $this->commandBus->dispatch(new ResumePendingSubmissionsCommand($session));

        $repository = $this->queryBus->ask(new GetLinkedRepositoryQuery($session));
        $commits = [];
        $githubError = null;
        if ($repository?->isUsable() && ! $isLocked) {
            try {
                $commits = $this->queryBus->ask(new ListRecentCommitsQuery($session, 10));
            } catch (HostUnavailable $e) {
                Log::warning("Listing commits failed for session {$session}: {$e->getMessage()}");
                $githubError = 'We couldn\'t load your commits from GitHub just now. Refresh in a minute.';
            }
        }

        $attempts = $this->queryBus->ask(new ListAttemptsQuery($session, $task));

        return view('learn.milestone', [
            'form'         => $form,
            'sessionId'    => $session,
            'taskId'       => $task,
            'projectId'    => $sessionView->projectId,
            'variant'      => $this->queryBus->ask(new GetStackVariantQuery($sessionView->stackVariantId)),
            'spec'         => $this->queryBus->ask(new GetTaskVariantSpecQuery($task, $sessionView->stackVariantId)),
            'isLocked'     => $isLocked,
            'isPassed'     => in_array($task, $progress->passedTaskIds, true),
            'repository'   => $repository,
            'commits'      => $commits,
            'githubError'  => $githubError,
            'attempts'     => $attempts,
            'waiting'      => collect($attempts)->contains(fn ($a) => $a->isWaitingForTests() || ($a->ciStatus !== null && $a->passed === null)),
            'guideContext' => ['testsFailed' => isset($attempts[0]) && in_array($attempts[0]->ciStatus, ['failed', 'errored', 'not_run'], true)],
        ]);
    }

    public function store(Request $request, string $session, string $task): RedirectResponse
    {
        $data = $request->validate([
            'commit_sha'  => ['required', 'string', 'regex:/^[0-9a-f]{7,40}$/i'],
            'explanation' => ['required', 'string', 'min:20', 'max:10000'],
        ], [
            'commit_sha.required'  => 'Choose the commit that completes this milestone.',
            'explanation.min'      => 'Say a little more: what you changed, why, and how you checked it.',
        ]);

        try {
            $this->commandBus->dispatch(new SubmitMilestoneCommand(Auth::id(), $session, $task, $data['commit_sha'], $data['explanation']));
        } catch (SubmissionNotAllowedException|LearnerNotFoundException $e) {
            return back()->withInput()->withErrors(['submission' => ucfirst($e->getMessage()) . '.']);
        }

        return redirect()->route('learn.milestone', [$session, $task])
            ->with('success', 'Submitted. Your tests run on GitHub; the review starts as soon as they finish.');
    }

    public function refresh(string $session, string $task): RedirectResponse
    {
        // Only the session's own learner may prompt its submissions (the form query checks ownership).
        abort_if($this->queryBus->ask(new GetSubmissionFormQuery(Auth::id(), $session, $task)) === null, 404);

        $this->commandBus->dispatch(new ResumePendingSubmissionsCommand($session));

        return redirect()->route('learn.milestone', [$session, $task]);
    }
}
