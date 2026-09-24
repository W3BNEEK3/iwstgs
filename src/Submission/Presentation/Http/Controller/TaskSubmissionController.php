<?php
namespace Src\Submission\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\EvalEngine\Application\Query\GetEvaluationResult\GetEvaluationResultQuery;
use Src\SimExecution\Application\Query\GetLearnerSession\GetLearnerSessionQuery;
use Src\Submission\Application\Command\SubmitTask\SubmitTaskCommand;
use Src\Submission\Application\Query\GetLatestSubmissionId\GetLatestSubmissionIdQuery;
use Src\Submission\Application\Query\GetSubmissionForm\GetSubmissionFormQuery;
use Src\Submission\Domain\Exceptions\LearnerNotFoundException;
use Src\Submission\Domain\Exceptions\MissingRequiredDeliverableException;
use Src\Submission\Domain\Exceptions\SubmissionNotAllowedException;
use Src\Submission\Infrastructure\Storage\ArtifactStorageService;
use Src\Submission\Presentation\Http\Request\SubmitTaskRequest;

class TaskSubmissionController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
        private readonly ArtifactStorageService $storage,
    ) {}

    public function show(string $session, string $task): View
    {
        $form = $this->queryBus->ask(new GetSubmissionFormQuery(Auth::id(), $session, $task));
        abort_if($form === null, 404);

        return view('learn.task-submit', ['sessionId' => $session, 'taskId' => $task, 'form' => $form]);
    }

    public function store(SubmitTaskRequest $request, string $session, string $task): RedirectResponse
    {
        // The form renders one file input per file-type deliverable (keyed by deliverable id),
        // so each upload can be tagged with the deliverable's own declared type rather than a
        // generic catch-all.
        $form = $this->queryBus->ask(new GetSubmissionFormQuery(Auth::id(), $session, $task));
        abort_if($form === null, 404);
        $deliverableTypes = collect($form->deliverables)->pluck('type', 'id');

        $artifacts = [];
        foreach ($request->file('artifacts', []) as $deliverableId => $files) {
            $artifactType = match ($deliverableTypes[$deliverableId] ?? null) {
                'diagram'  => 'diagram',
                'document' => 'document',
                default    => 'other',
            };

            foreach ($files as $file) {
                $stored = $this->storage->store($session, $task, $file);
                $artifacts[] = [
                    'filename'    => $stored['filename'],
                    'storagePath' => $stored['storagePath'],
                    'type'        => $artifactType,
                ];
            }
        }

        try {
            $this->commandBus->dispatch(new SubmitTaskCommand(
                userId:      Auth::id(),
                sessionId:   $session,
                taskId:      $task,
                layer1Text:  $request->input('layer1_text'),
                layer3Code:  $request->input('layer3_code'),
                artifacts:   $artifacts,
            ));
        } catch (LearnerNotFoundException|SubmissionNotAllowedException $e) {
            abort(404);
        } catch (MissingRequiredDeliverableException $e) {
            return back()->withErrors(['submission' => ucfirst($e->getMessage()) . '.'])->withInput();
        }

        // Evaluation (when the aimediation.claude_evaluation flag is on) runs synchronously
        // inside the SubmissionReceived listener, so by the time control returns here it has
        // already completed (or failed and was swallowed). Integration Spec §8/§14: the only
        // specified learner-facing output of a pass or fail is what appears on the board (task
        // completed, or an amber consequence card) — never a direct grade reveal. The one
        // genuinely distinct specified UI moment is an uncertain evaluation ("Prompt modal —
        // learner answers before result recorded"), so only that case routes to a dedicated page.
        $submissionId = $this->queryBus->ask(new GetLatestSubmissionIdQuery($session, $task));
        if ($submissionId !== null) {
            $evaluation = $this->queryBus->ask(new GetEvaluationResultQuery(Auth::id(), $submissionId));
            if ($evaluation !== null && $evaluation->isUncertain) {
                return redirect()->route('learn.evaluation-result', $submissionId);
            }
        }

        $sessionView = $this->queryBus->ask(new GetLearnerSessionQuery($session));

        if ($sessionView->status === 'diagnostic') {
            return redirect()->route('learn.diagnostic')
                ->with('success', 'Submission received.');
        }

        return redirect()->route('learn.sprint-board', $sessionView->projectId)
            ->with('success', 'Submission received.');
    }
}
