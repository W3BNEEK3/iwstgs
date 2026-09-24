<?php
namespace Src\SimExecution\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class LearnerNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('no learner record exists for this user yet');
    }
}
