<?php
namespace Src\Submission\Application\Service;

use Illuminate\Support\Facades\Log;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Query\GetBuildProgress\GetBuildProgressQuery;
use Src\SimExecution\Application\Query\GetLearnerSession\GetLearnerSessionQuery;
use Src\Simulation\Application\Query\GetStackVariant\GetStackVariantQuery;
use Src\Simulation\Application\Query\GetTaskVariantSpec\GetTaskVariantSpecQuery;
use Src\Simulation\Application\Query\ListAcceptanceTestsByTask\ListAcceptanceTestsByTaskQuery;
use Src\Simulation\Domain\Build\StackVariant;
use Src\SourceControl\Application\Query\GetCiSnapshot\GetCiSnapshotQuery;
use Src\SourceControl\Domain\CiSnapshot;
use Src\SourceControl\Domain\HostUnavailable;
use Src\Submission\Domain\Submission\AcceptanceResult;
use Src\Submission\Domain\Submission\SubmissionPackageDetail;
use Src\Submission\Domain\Submission\SubmissionPackageRepository;
use Src\Submission\Domain\Submission\SubmissionReceived;

/**
 * A milestone submission waits for its commit's test run. Once the run has
 * finished (or none appeared in time), the result is recorded and the
 * submission is handed to evaluation exactly like a pasted one, by firing
 * SubmissionReceived. Called on submit, from the workflow_run webhook, when
 * the learner refreshes, and by a scheduled sweep — whichever comes first.
 */
final class CiFinaliser
{
    /** How long to wait for a run to appear before evaluating without tests (design doc v2-01 §2.2). */
    public const WAIT_MINUTES = 15;

    public function __construct(
        private readonly SubmissionPackageRepository $packages,
        private readonly QueryBus $queryBus,
    ) {}

    /** @return bool true when the submission's CI result is now final */
    public function finalise(string $submissionId): bool
    {
        $submission = $this->packages->findDetailById($submissionId);
        if ($submission === null || $submission->ciStatus !== 'pending' || $submission->commitSha === null) {
            return $submission !== null && $submission->ciStatus !== 'pending';
        }

        $variant = $this->variantFor($submission);

        try {
            $ci = $this->queryBus->ask(new GetCiSnapshotQuery($submission->learnerSessionId, $submission->commitSha, $variant?->workflowSha256));
        } catch (HostUnavailable $e) {
            Log::warning("CI lookup for submission {$submissionId} failed: {$e->getMessage()}");

            return false; // try again on the next prompt
        }

        if ($ci->state !== CiSnapshot::FINISHED) {
            $waitedTooLong = $submission->submittedAt !== null
                && strtotime($submission->submittedAt) <= now()->subMinutes(self::WAIT_MINUTES)->getTimestamp();
            if (! $waitedTooLong) {
                return false;
            }

            $result = $ci->state === CiSnapshot::NO_RUN ? AcceptanceResult::notRun() : null;
            if ($result === null) {
                return false; // still running: keep waiting for the run itself
            }
        } else {
            [$introduced, $earlier] = $this->requiredTests($submission, $variant?->id);
            $result = AcceptanceResult::fromReport($ci->report, $introduced, $earlier, $ci->conclusion, $ci->workflowIntact);
        }

        $this->packages->recordCi($submissionId, $result->status, $ci->runUrl, $result->toArray());

        event(new SubmissionReceived(
            submissionId:     $submission->id,
            learnerId:        $submission->learnerId,
            learnerSessionId: $submission->learnerSessionId,
            taskId:           $submission->taskId,
            attemptNumber:    $submission->attemptNumber,
        ));

        return true;
    }

    /**
     * Tests this task introduces, and the tests of every milestone already
     * passed in the session (they must keep passing).
     *
     * @return array{0: string[], 1: string[]}
     */
    private function requiredTests(SubmissionPackageDetail $submission, ?string $variantId): array
    {
        if ($variantId === null) {
            return [[], []];
        }

        $introduced = $this->queryBus->ask(new GetTaskVariantSpecQuery($submission->taskId, $variantId))?->acceptanceTests ?? [];
        $byTask = $this->queryBus->ask(new ListAcceptanceTestsByTaskQuery($variantId));
        $progress = $this->queryBus->ask(new GetBuildProgressQuery($submission->learnerSessionId));

        $earlier = [];
        foreach ($progress?->passedTaskIds ?? [] as $taskId) {
            if ($taskId !== $submission->taskId) {
                array_push($earlier, ...($byTask[$taskId] ?? []));
            }
        }

        return [$introduced, array_values(array_unique($earlier))];
    }

    private function variantFor(SubmissionPackageDetail $submission): ?StackVariant
    {
        $session = $this->queryBus->ask(new GetLearnerSessionQuery($submission->learnerSessionId));

        return $session?->stackVariantId === null ? null : $this->queryBus->ask(new GetStackVariantQuery($session->stackVariantId));
    }
}
