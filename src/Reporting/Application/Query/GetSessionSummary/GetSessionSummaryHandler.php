<?php
namespace Src\Reporting\Application\Query\GetSessionSummary;

use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Query\GetLearnerIdForUser\GetLearnerIdForUserQuery;
use Src\SimExecution\Application\Query\GetLearnerSession\GetLearnerSessionQuery;
use Src\Simulation\Application\Query\GetProject\GetProjectQuery;
use Src\Submission\Application\Query\ListSubmissionsForLearner\ListSubmissionsForLearnerQuery;
use Src\Submission\Domain\Submission\SubmissionTrajectoryEntry;

final class GetSessionSummaryHandler
{
    public function __construct(private readonly QueryBus $queryBus) {}

    public function handle(GetSessionSummaryQuery $query): ?SessionSummaryView
    {
        if ($query->userId === null) {
            return null;
        }

        $learnerId = $this->queryBus->ask(new GetLearnerIdForUserQuery($query->userId));
        if ($learnerId === null) {
            return null;
        }

        $session = $this->queryBus->ask(new GetLearnerSessionQuery($query->sessionId));
        if ($session === null || $session->learnerId !== $learnerId) {
            return null;
        }

        $project = $this->queryBus->ask(new GetProjectQuery($session->projectId));

        /** @var SubmissionTrajectoryEntry[] $allSubmissions */
        $allSubmissions = $this->queryBus->ask(new ListSubmissionsForLearnerQuery($learnerId));
        $submissions = array_values(array_filter(
            $allSubmissions,
            fn (SubmissionTrajectoryEntry $s) => $s->learnerSessionId === $query->sessionId,
        ));

        return new SessionSummaryView(
            sessionId:      $session->id,
            projectId:      $session->projectId,
            projectTitle:   $project?->title() ?? '(project unavailable)',
            status:         $session->status,
            submissions:    $submissions,
        );
    }
}
