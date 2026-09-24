<?php
namespace Src\SimExecution\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class InvalidSprintTransitionException extends DomainException
{
    public function __construct(string $reason)
    {
        parent::__construct($reason);
    }
}
