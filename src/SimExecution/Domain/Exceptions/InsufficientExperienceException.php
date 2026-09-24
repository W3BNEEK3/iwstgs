<?php
namespace Src\SimExecution\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class InsufficientExperienceException extends DomainException
{
    public function __construct()
    {
        parent::__construct('your declared experience does not meet this role\'s minimum requirement');
    }
}
