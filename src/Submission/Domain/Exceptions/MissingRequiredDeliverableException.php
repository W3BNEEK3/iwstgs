<?php
namespace Src\Submission\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class MissingRequiredDeliverableException extends DomainException
{
    public function __construct(string $label)
    {
        parent::__construct("the required deliverable \"{$label}\" is missing");
    }
}
