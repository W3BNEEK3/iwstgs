<?php
namespace Src\Submission\Application\Query\ListAttempts;

use Src\EvalEngine\Application\Query\GetSubmissionPassStatus\GetSubmissionPassStatusQuery;
use Src\Shared\Application\Bus\QueryBus;
use Src\Submission\Domain\Submission\SubmissionPackageRepository;

/** Every attempt at a task, newest first, with its test run and outcome — the milestone page's history. */
final class ListAttemptsHandler
{
    public function __construct(
        private readonly SubmissionPackageRepository $packages,
        private readonly QueryBus $queryBus,
    ) {}

    /** @return AttemptView[] */
    public function handle(ListAttemptsQuery $query): array
    {
        $attempts = [];
        foreach ($this->packages->attemptIds($query->learnerSessionId, $query->taskId) as $id) {
            $s = $this->packages->findDetailById($id);
            if ($s === null) {
                continue;
            }
            $attempts[] = new AttemptView(
                submissionId:  $s->id,
                attemptNumber: $s->attemptNumber,
                submittedAt:   $s->submittedAt,
                commitSha:     $s->commitSha,
                ciStatus:      $s->ciStatus,
                ciSummary:     $s->ciReport['summary'] ?? null,
                ciRunUrl:      $s->ciRunUrl,
                tests:         $s->ciReport['tests'] ?? null,
                passed:        $this->queryBus->ask(new GetSubmissionPassStatusQuery($s->id)),
            );
        }

        return $attempts;
    }
}
