<?php

namespace Src\AIMediation\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Str;
use Src\AIMediation\Domain\Audit\ActionTaken;
use Src\AIMediation\Domain\Audit\AimediationEventRepository;
use Src\AIMediation\Domain\Audit\TriggerType;
use Src\AIMediation\Infrastructure\Persistence\Eloquent\Model\AimediationEventModel;

final class EloquentAimediationEventRepository implements AimediationEventRepository
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
    ): void {
        AimediationEventModel::create([
            'id'                => (string) Str::uuid(),
            'learner_id'        => $learnerId,
            'session_id'        => $sessionId,
            'trigger_type'      => $triggerType,
            'trigger_source_id' => $triggerSourceId,
            'action_taken'      => $actionTaken,
            'action_detail'     => $actionDetail,
            'is_deterministic'  => $isDeterministic,
            'confidence_score'  => $confidenceScore,
        ]);
    }
}
