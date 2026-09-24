<?php
namespace Src\SimExecution\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class SessionNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('no active session exists for this learner and project');
    }
}
