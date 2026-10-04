<?php
namespace Src\Submission\Application\Query\ListAttempts;

final class AttemptView
{
    /** @param array|null $tests per-test results (see AcceptanceResult::toArray()['tests']) */
    public function __construct(
        public readonly string $submissionId,
        public readonly int $attemptNumber,
        public readonly ?string $submittedAt,
        public readonly ?string $commitSha,
        public readonly ?string $ciStatus,
        public readonly ?string $ciSummary,
        public readonly ?string $ciRunUrl,
        public readonly ?array $tests,
        /** true / false once evaluated; null while waiting for tests or the review */
        public readonly ?bool $passed,
    ) {}

    public function isWaitingForTests(): bool
    {
        return $this->ciStatus === 'pending';
    }

    public function shortSha(): ?string
    {
        return $this->commitSha === null ? null : substr($this->commitSha, 0, 7);
    }
}
