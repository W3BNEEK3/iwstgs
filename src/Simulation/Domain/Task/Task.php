<?php
namespace Src\Simulation\Domain\Task;

use Src\Shared\Domain\AggregateRoot;
use Src\Simulation\Domain\Cac\CacLevel;

final class Task extends AggregateRoot
{
    public function __construct(
        private readonly TaskId $id,
        private readonly string $scenarioId,
        private int    $sequenceOrder,
        private string $title,
        private string $taskBrief,
        private ?string $domain,
        private TaskType $taskType,
        private array  $roleTags,
        private array  $tools,
        private array  $prerequisiteConcepts,
        private bool   $isCacRuntimeSet,
        private ?CacLevel $fixedComplexity,
        private ?CacLevel $fixedAutonomy,
        private ?CacLevel $fixedContextFidelity,
        private array  $consequenceTaskIds,
        private array  $suggestionTaskIds,
        private bool   $isArchitectural,
        private bool   $planningLayerActive,
        private ?array $codeExecutionConfig,
        private ?string $modelResponseSummary,
        private ?int   $timeLimitMinutes,
        private bool   $isPublished,
        private bool   $isActive,
        // children
        private array $expectedDeliverables = [],
        private array $cacVariants = [],
        private array $dependencies = [],
        private array $knowledgeAnchors = [],
        private array $guidancePrompts = [],
    ) {}

    public static function create(
        TaskId $id, string $scenarioId, int $sequenceOrder, string $title, string $taskBrief,
        TaskType $taskType, bool $isCacRuntimeSet, bool $isArchitectural, bool $planningLayerActive,
        ?string $domain = null, array $roleTags = [], array $tools = [], array $prerequisiteConcepts = [],
        ?CacLevel $fixedComplexity = null, ?CacLevel $fixedAutonomy = null, ?CacLevel $fixedContextFidelity = null,
        array $consequenceTaskIds = [], array $suggestionTaskIds = [], ?array $codeExecutionConfig = null,
        ?string $modelResponseSummary = null, ?int $timeLimitMinutes = null,
    ): self {
        $t = new self(
            $id, $scenarioId, $sequenceOrder, $title, $taskBrief, $domain, $taskType,
            $roleTags, $tools, $prerequisiteConcepts, $isCacRuntimeSet,
            $fixedComplexity, $fixedAutonomy, $fixedContextFidelity,
            $consequenceTaskIds, $suggestionTaskIds, $isArchitectural, $planningLayerActive,
            $codeExecutionConfig, $modelResponseSummary, $timeLimitMinutes,
            isPublished: false, isActive: true,
        );
        $t->recordEvent(new TaskCreated((string) $id, $scenarioId, $title));
        return $t;
    }

    public static function reconstitute(
        TaskId $id, string $scenarioId, int $sequenceOrder, string $title, string $taskBrief,
        TaskType $taskType, bool $isCacRuntimeSet, bool $isArchitectural, bool $planningLayerActive,
        bool $isPublished, bool $isActive,
        ?string $domain = null, array $roleTags = [], array $tools = [], array $prerequisiteConcepts = [],
        ?CacLevel $fixedComplexity = null, ?CacLevel $fixedAutonomy = null, ?CacLevel $fixedContextFidelity = null,
        array $consequenceTaskIds = [], array $suggestionTaskIds = [], ?array $codeExecutionConfig = null,
        ?string $modelResponseSummary = null, ?int $timeLimitMinutes = null,
        array $expectedDeliverables = [], array $cacVariants = [], array $dependencies = [],
        array $knowledgeAnchors = [], array $guidancePrompts = [],
    ): self {
        return new self(
            $id, $scenarioId, $sequenceOrder, $title, $taskBrief, $domain, $taskType,
            $roleTags, $tools, $prerequisiteConcepts, $isCacRuntimeSet,
            $fixedComplexity, $fixedAutonomy, $fixedContextFidelity,
            $consequenceTaskIds, $suggestionTaskIds, $isArchitectural, $planningLayerActive,
            $codeExecutionConfig, $modelResponseSummary, $timeLimitMinutes,
            $isPublished, $isActive,
            $expectedDeliverables, $cacVariants, $dependencies, $knowledgeAnchors, $guidancePrompts,
        );
    }

    // --- child management ---
    public function addExpectedDeliverable(ExpectedDeliverable $d): void { $this->expectedDeliverables[] = $d; }
    public function addCacVariant(CacVariant $v): void { $this->cacVariants[] = $v; }
    public function addDependency(TaskDependencyLink $d): void { $this->dependencies[] = $d; }
    public function addKnowledgeAnchor(KnowledgeAnchor $a): void { $this->knowledgeAnchors[] = $a; }
    public function addGuidancePrompt(GuidancePrompt $p): void { $this->guidancePrompts[] = $p; }

    public function removeChild(string $collection, string $childId): void
    {
        $map = [
            'deliverables' => 'expectedDeliverables', 'cacVariants' => 'cacVariants',
            'dependencies' => 'dependencies', 'anchors' => 'knowledgeAnchors', 'prompts' => 'guidancePrompts',
        ];
        $prop = $map[$collection] ?? null;
        if ($prop === null) return;
        $this->$prop = array_values(array_filter($this->$prop, fn ($c) => $c->id() !== $childId));
    }

    // --- own behaviour ---
    public function rename(string $t): void { $this->title = $t; }
    public function publish(): void { $this->isPublished = true; }
    public function unpublish(): void { $this->isPublished = false; }

    public function id(): string { return (string) $this->id; }
    public function isPublished(): bool { return $this->isPublished; }
    public function expectedDeliverables(): array { return $this->expectedDeliverables; }
    public function cacVariants(): array { return $this->cacVariants; }
    public function dependencies(): array { return $this->dependencies; }
    public function knowledgeAnchors(): array { return $this->knowledgeAnchors; }
    public function guidancePrompts(): array { return $this->guidancePrompts; }

    public function toPrimitives(): array
    {
        return [
            'id' => (string) $this->id, 'scenario_id' => $this->scenarioId, 'sequence_order' => $this->sequenceOrder,
            'title' => $this->title, 'task_brief' => $this->taskBrief, 'domain' => $this->domain,
            'task_type' => $this->taskType->value, 'role_tags' => $this->roleTags, 'tools' => $this->tools,
            'prerequisite_concepts' => $this->prerequisiteConcepts, 'is_cac_runtime_set' => $this->isCacRuntimeSet,
            'fixed_complexity' => $this->fixedComplexity?->value, 'fixed_autonomy' => $this->fixedAutonomy?->value,
            'fixed_context_fidelity' => $this->fixedContextFidelity?->value,
            'consequence_task_ids' => $this->consequenceTaskIds, 'suggestion_task_ids' => $this->suggestionTaskIds,
            'is_architectural' => $this->isArchitectural, 'planning_layer_active' => $this->planningLayerActive,
            'code_execution_config' => $this->codeExecutionConfig, 'model_response_summary' => $this->modelResponseSummary,
            'time_limit_minutes' => $this->timeLimitMinutes, 'is_published' => $this->isPublished, 'is_active' => $this->isActive,
        ];
    }
}
