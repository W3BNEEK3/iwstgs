<?php
namespace Src\AIMediation\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class ReviewEntryNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('no review entry exists for this id');
    }
}
