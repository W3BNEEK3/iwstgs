<?php
namespace Src\SimExecution\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class AlreadyEnrolledInProjectException extends DomainException
{
    public function __construct()
    {
        parent::__construct('you are already enrolled in this project');
    }
}
