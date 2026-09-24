<?php
namespace Src\SimExecution\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class RoleNotAvailableForProjectException extends DomainException
{
    public function __construct()
    {
        parent::__construct('that role is not available for this project');
    }
}
