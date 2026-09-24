<?php
namespace Src\Simulation\Application\Command\CreateTask;

final class CreateTaskCommand
{
    public function __construct(
        public readonly string  $scenarioId,
        public readonly string  $title,
        public readonly string  $taskBrief,
        public readonly string  $taskType,
        public readonly bool    $isCacRuntimeSet,
        public readonly bool    $isArchitectural,
        public readonly bool    $planningLayerActive,
        public readonly ?string $domain = null,
        public readonly array   $roleTags = [],
        public readonly array   $tools = [],
        public readonly array   $prerequisiteConcepts = [],
        public readonly ?string $fixedComplexity = null,
        public readonly ?string $fixedAutonomy = null,
        public readonly ?string $fixedContextFidelity = null,
        public readonly array   $consequenceTaskIds = [],
        public readonly array   $suggestionTaskIds = [],
        public readonly ?string $modelResponseSummary = null,
        public readonly ?int    $timeLimitMinutes = null,
    ) {}
}
