<?php
namespace Src\Identity\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class EmailAlreadyTakenException extends DomainException
{
    public function __construct(string $email)
    {
        parent::__construct("The email address '{$email}' is already registered");
    }
}