<?php
namespace Src\Submission\Application\Query\ListAttempts;

final class ListAttemptsQuery
{
    public function __construct(
        public readonly string $learnerSessionId,
        public readonly string $taskId,
    ) {}
}
