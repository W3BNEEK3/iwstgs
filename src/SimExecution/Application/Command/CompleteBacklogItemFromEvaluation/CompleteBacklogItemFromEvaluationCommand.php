<?php
namespace Src\SimExecution\Application\Command\CompleteBacklogItemFromEvaluation;

final class CompleteBacklogItemFromEvaluationCommand
{
    public function __construct(public readonly string $backlogItemId) {}
}
