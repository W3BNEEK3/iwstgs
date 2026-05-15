<?php
namespace Src\Identity\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class AlreadyEnrolledException extends DomainException
{
    public function __construct()
    {
        parent::__construct("The user is already enrolled in the system");
    }
}