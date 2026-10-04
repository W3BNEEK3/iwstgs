<?php
namespace Src\SimExecution\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class StackVariantNotAvailableException extends DomainException
{
    public function __construct(string $reason = 'please choose one of the stacks offered for this project')
    {
        parent::__construct($reason);
    }
}
