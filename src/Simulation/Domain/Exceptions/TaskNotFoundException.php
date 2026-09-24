<?php
namespace Src\Simulation\Domain\Exceptions;

final class TaskNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Task [{$id}] was not found.");
    }
}
