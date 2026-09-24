<?php
namespace Src\AIMediation\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class InvalidReviewTransitionException extends DomainException
{
    public function __construct(string $reason)
    {
        parent::__construct($reason);
    }
}
