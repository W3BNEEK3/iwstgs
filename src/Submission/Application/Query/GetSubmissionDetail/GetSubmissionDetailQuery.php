<?php
namespace Src\Submission\Application\Query\GetSubmissionDetail;

final class GetSubmissionDetailQuery
{
    public function __construct(public readonly string $submissionId) {}
}
