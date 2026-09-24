<?php
namespace Src\Submission\Application\Query\CountSubmissionAttempts;

final class CountSubmissionAttemptsQuery
{
    public function __construct(
        public readonly string $learnerSessionId,
        public readonly string $taskId,
    ) {}
}
