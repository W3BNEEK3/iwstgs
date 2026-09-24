<?php
namespace Src\EvalEngine\Application\Query\GetSubmissionPassStatus;

final class GetSubmissionPassStatusQuery
{
    public function __construct(
        public readonly string $submissionId,
    ) {}
}
