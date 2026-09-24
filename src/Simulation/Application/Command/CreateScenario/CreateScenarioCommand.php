<?php
namespace Src\Simulation\Application\Command\CreateScenario;



final class CreateScenarioCommand
{
    public function __construct(
        public readonly string  $projectId,
        public readonly string  $title,
        public readonly string  $narrativeContext,
        public readonly string  $situationTrigger,
        public readonly string  $situationTriggerType,
        public readonly string  $defaultAutonomyLevel,
        public readonly string  $learnerRoleLabel,
        public readonly bool    $isDiagnostic = false,
    ) {}
}