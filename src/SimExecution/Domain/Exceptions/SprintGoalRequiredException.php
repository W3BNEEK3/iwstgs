<?php
namespace Src\SimExecution\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class SprintGoalRequiredException extends DomainException
{
    public function __construct()
    {
        parent::__construct('a sprint goal is required before this sprint can be confirmed');
    }
}
