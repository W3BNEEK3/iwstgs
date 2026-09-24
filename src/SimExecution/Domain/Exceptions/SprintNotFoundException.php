<?php
namespace Src\SimExecution\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class SprintNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('no sprint record exists for this id');
    }
}
