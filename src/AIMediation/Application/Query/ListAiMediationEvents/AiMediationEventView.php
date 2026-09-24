<?php
namespace Src\AIMediation\Application\Query\ListAiMediationEvents;

final class AiMediationEventView
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $createdAt,
        public readonly string  $learnerId,
        public readonly ?string $learnerName,
        public readonly string  $sessionId,
        public readonly string  $triggerType,
        public readonly ?string $triggerSourceId,
        public readonly string  $actionTaken,
        public readonly ?array  $actionDetail,
        public readonly bool    $isDeterministic,
        public readonly ?string $confidenceScore,
    ) {}
}
