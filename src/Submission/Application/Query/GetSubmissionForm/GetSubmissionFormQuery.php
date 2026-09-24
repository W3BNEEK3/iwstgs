<?php
namespace Src\Submission\Application\Query\GetSubmissionForm;

final class GetSubmissionFormQuery
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $sessionId,
        public readonly string $taskId,
    ) {}
}
