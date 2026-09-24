<?php
namespace Src\Simulation\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class ScenarioNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("The scenario with ID '{$id}' was not found");
    }
}
