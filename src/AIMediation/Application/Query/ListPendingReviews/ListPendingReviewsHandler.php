<?php
namespace Src\AIMediation\Application\Query\ListPendingReviews;

use Src\AIMediation\Domain\Review\HumanReviewRepository;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Query\GetLearnerName\GetLearnerNameQuery;
use Src\Simulation\Application\Query\GetTask\GetTaskQuery;
use Src\Submission\Application\Query\GetSubmissionDetail\GetSubmissionDetailQuery;

final class ListPendingReviewsHandler
{
    public function __construct(
        private readonly HumanReviewRepository $humanReviews,
        private readonly QueryBus $queryBus,
    ) {}

    /** @return PendingReviewView[] */
    public function handle(ListPendingReviewsQuery $query): array
    {
        $views = [];

        foreach ($this->humanReviews->findActive() as $entry) {
            $submission = $this->queryBus->ask(new GetSubmissionDetailQuery($entry->submissionId()));
            $taskTitle = '(submission unavailable)';
            $learnerName = '(unknown learner)';

            if ($submission !== null) {
                $task = $this->queryBus->ask(new GetTaskQuery($submission->submission->taskId));
                $taskTitle = $task?->toPrimitives()['title'] ?? $taskTitle;
                $learnerName = $this->queryBus->ask(new GetLearnerNameQuery($submission->submission->learnerId)) ?? $learnerName;
            }

            $views[] = new PendingReviewView(
                id:           $entry->id(),
                submissionId: $entry->submissionId(),
                learnerName:  $learnerName,
                taskTitle:    $taskTitle,
                queuedAt:     $entry->queuedAt(),
                status:       $entry->status()->value,
            );
        }

        return $views;
    }
}
