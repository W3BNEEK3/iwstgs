<?php
namespace Src\Submission\Domain\Submission;

use Src\Shared\Domain\DomainEvent;

/**
 * No aggregate to hang this on (submission_packages is byproduct-pattern),
 * so SubmitTaskHandler fires it directly with the global event() helper
 * after persistence succeeds, rather than via an AggregateRoot's
 * recordEvent()/releaseEvents() cycle. EvalEngine listens for this to
 * trigger Claude evaluation (Phase 8).
 */
final class SubmissionReceived extends DomainEvent
{
    public function __construct(
        public readonly string $submissionId,
        public readonly string $learnerId,
        public readonly string $learnerSessionId,
        public readonly string $taskId,
        public readonly int $attemptNumber,
    ) {
        parent::__construct();
    }
}
