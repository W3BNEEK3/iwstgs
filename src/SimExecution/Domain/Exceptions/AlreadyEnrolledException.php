<?php
namespace Src\SimExecution\Domain\Exceptions;
 
use Src\Shared\Domain\DomaimException;

final class AlreadyEnrolledException extends DomainException
{
    public function __construct()
    {
        parent::__construct('this user is already enrolled as a learner');
    }
}