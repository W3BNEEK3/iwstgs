<?php
namespace Src\AIMediation\Application\Command\ResolveReview;

use Src\AIMediation\Domain\Audit\ActionTaken;
use Src\AIMediation\Domain\Audit\AimediationEventRepository;
use Src\AIMediation\Domain\Audit\TriggerType;
use Src\AIMediation\Domain\Exceptions\ReviewEntryNotFoundException;
use Src\AIMediation\Domain\Review\HumanReviewRepository;
use Src\AIMediation\Domain\Review\ReviewerDecision;
use Src\LearnerProfile\Application\Command\RecordEvaluationOutcome\RecordEvaluationOutcomeCommand;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Command\CompleteBacklogItemFromEvaluation\CompleteBacklogItemFromEvaluationCommand;
use Src\SimExecution\Application\Query\FindBacklogItemForTask\FindBacklogItemForTaskQuery;
use Src\SimExecution\Application\Query\GetLearnerSession\GetLearnerSessionQuery;
use Src\Submission\Application\Query\GetSubmissionDetail\GetSubmissionDetailQuery;

/**
 * A human resolution has the same downstream effect Claude's own passing/
 * failing verdict would have (PostEvaluationRouter) — the reviewer is
 * standing in for an uncertain automated call, not creating a separate
 * category of outcome. "Escalate" is recorded but takes no further action;
 * there's nothing above a human reviewer in this system yet.
 */
final class ResolveReviewHandler
{
    public function __construct(
        private readonly HumanReviewRepository $humanReviews,
        private readonly AimediationEventRepository $aimediationEvents,
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    public function handle(ResolveReviewCommand $command): void
    {
        $entry = $this->humanReviews->findById($command->reviewEntryId);
        if ($entry === null) {
            throw new ReviewEntryNotFoundException();
        }

        $decision = ReviewerDecision::from($command->decision);
        $entry->resolve($decision, $command->notes);
        $this->humanReviews->save($entry);

        $submission = $this->queryBus->ask(new GetSubmissionDetailQuery($entry->submissionId()));

        if ($decision !== ReviewerDecision::Escalate && $submission !== null) {
            $passed = $decision === ReviewerDecision::Proficient;
            $this->commandBus->dispatch(new RecordEvaluationOutcomeCommand($entry->learnerId(), $passed));

            if ($passed) {
                $session = $this->queryBus->ask(new GetLearnerSessionQuery($submission->submission->learnerSessionId));
                if ($session !== null) {
                    $backlogItemId = $this->queryBus->ask(new FindBacklogItemForTaskQuery(
                        learnerSessionId: $submission->submission->learnerSessionId,
                        taskId:           $submission->submission->taskId,
                        projectId:        $session->projectId,
                    ));
                    if ($backlogItemId !== null) {
                        $this->commandBus->dispatch(new CompleteBacklogItemFromEvaluationCommand($backlogItemId));
                    }
                }
            }
        }

        if ($submission !== null) {
            $this->aimediationEvents->record(
                learnerId:        $entry->learnerId(),
                sessionId:        $submission->submission->learnerSessionId,
                triggerType:      TriggerType::UncertainEvaluation,
                triggerSourceId:  $entry->submissionId(),
                actionTaken:      $decision === ReviewerDecision::Proficient ? ActionTaken::TaskCompleted : ActionTaken::NoAction,
                actionDetail:     ['reviewer_decision' => $decision->value, 'notes' => $command->notes],
                isDeterministic:  true,
            );
        }
    }
}
