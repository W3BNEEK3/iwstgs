<?php
namespace Src\SourceControl\Application\Listener;

use Src\EvalEngine\Domain\Evaluation\EvaluationComplete;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\SourceControl\Application\Command\MarkCommitAccepted\MarkCommitAcceptedCommand;
use Src\Submission\Application\Query\GetSubmissionDetail\GetSubmissionDetailQuery;

/** A passed milestone's commit is the new baseline: the next milestone's changes are compared against it. */
final class AcceptCommitOnPass
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly CommandBus $commandBus,
    ) {}

    public function handle(EvaluationComplete $event): void
    {
        if (! $event->passesThreshold) {
            return;
        }

        $submission = $this->queryBus->ask(new GetSubmissionDetailQuery($event->submissionId))?->submission;
        if ($submission !== null && $submission->isFromRepository() && $submission->commitSha !== null) {
            $this->commandBus->dispatch(new MarkCommitAcceptedCommand($event->learnerSessionId, $submission->commitSha));
        }
    }
}
