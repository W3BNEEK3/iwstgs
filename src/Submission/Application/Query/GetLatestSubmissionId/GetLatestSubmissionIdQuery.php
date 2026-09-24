<?php
namespace Src\Submission\Application\Query\GetLatestSubmissionId;

final class GetLatestSubmissionIdQuery
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $taskId,
    ) {}
}
