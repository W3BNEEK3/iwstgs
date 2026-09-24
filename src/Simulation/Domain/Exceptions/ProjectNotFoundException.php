<?php
namespace Src\Simulation\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class ProjectNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("The project with ID '{$id}' was not found");
    }
}