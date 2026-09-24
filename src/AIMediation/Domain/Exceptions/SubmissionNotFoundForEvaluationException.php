<?php
namespace Src\AIMediation\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class SubmissionNotFoundForEvaluationException extends DomainException
{
    public function __construct(string $submissionId)
    {
        parent::__construct("no submission found for id {$submissionId} — cannot build an evaluation prompt");
    }
}
