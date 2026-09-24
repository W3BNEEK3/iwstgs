<?php
namespace Src\Submission\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class SubmissionNotAllowedException extends DomainException
{
    public function __construct(string $reason)
    {
        parent::__construct($reason);
    }
}
