<?php
namespace Src\SimExecution\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class BacklogItemNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('no backlog item exists for this id');
    }
}
