<?php
namespace Src\SimExecution\Application\Command\TransitionScenarioIfComplete;

final class TransitionScenarioIfCompleteCommand
{
    public function __construct(public readonly string $learnerSessionId) {}
}
