<?php
namespace Src\Simulation\Domain\Build;

/**
 * A scripted story beat (design doc v2-01 §1.6): something that happens to
 * every learner at a planned moment — a stakeholder message, a change of
 * requirements, an incident. Adaptive events (consequences, suggestions)
 * stay with the EvalEngine.
 */
final class ScenarioEvent
{
    public const SCENARIO_START = 'scenario_start';
    public const AFTER_TASK = 'after_task';

    /**
     * @param string[]|null $roleTags only for learners in these roles; null = everyone
     * @param array{sender?: string, role?: string, text?: string} $payload
     */
    public function __construct(
        public readonly string $id,
        public readonly string $scenarioId,
        public readonly string $trigger,
        public readonly ?string $triggerTaskId,
        public readonly string $eventType,
        public readonly ?array $roleTags,
        public readonly array $payload,
        public readonly int $displayOrder,
    ) {}
}
