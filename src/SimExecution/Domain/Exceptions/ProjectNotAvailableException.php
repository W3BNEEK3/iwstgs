<?php
namespace Src\SimExecution\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class ProjectNotAvailableException extends DomainException
{
    public function __construct()
    {
        parent::__construct('this project is not currently available for enrolment');
    }
}
