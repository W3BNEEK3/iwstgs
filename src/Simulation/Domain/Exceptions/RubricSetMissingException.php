<?php
namespace Src\Simulation\Domain\Exceptions;

final class RubricSetMissingException extends \DomainException
{
    public function __construct(string $taskId)
    {
        parent::__construct("No rubric set found for the project of task [{$taskId}].");
    }
}
