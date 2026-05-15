<?php
namespace Src\Identity\Domain\Exception;

use Src\Shared\Domain\DomainException;

final class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__constuct("The provided credentials are not correct");
    }
}