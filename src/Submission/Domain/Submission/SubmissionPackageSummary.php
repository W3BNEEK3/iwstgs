<?php
namespace Src\Submission\Domain\Submission;

final class SubmissionPackageSummary
{
    public function __construct(
        public readonly string $id,
        public readonly string $taskId,
        public readonly int $attemptNumber,
        public readonly string $submittedAt,
    ) {}
}
