<?php
namespace Src\AIMediation\Domain\Audit;

/**
 * Immutable audit log — no domain aggregate, same reasoning as
 * SprintBoardEventRepository/RankEventRepository. Handlers call record()
 * inline after a mutation succeeds.
 */
interface AimediationEventRepository
{
    public function record(
        string $learnerId,
        string $sessionId,
        TriggerType $triggerType,
        ?string $triggerSourceId,
        ActionTaken $actionTaken,
        ?array $actionDetail,
        bool $isDeterministic,
        ?string $confidenceScore = null,
    ): void;
}
