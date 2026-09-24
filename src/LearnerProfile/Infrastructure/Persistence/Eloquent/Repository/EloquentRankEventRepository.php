<?php

namespace Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Str;
use Src\LearnerProfile\Domain\Rank\RankEventRepository;
use Src\LearnerProfile\Domain\Rank\RankEventSummary;
use Src\LearnerProfile\Domain\Rank\RankEventType;
use Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Model\RankEventModel;

final class EloquentRankEventRepository implements RankEventRepository
{
    public function record(
        string $learnerId,
        RankEventType $eventType,
        ?string $fromRankTier,
        ?int $fromRankLevel,
        ?string $toRankTier,
        ?int $toRankLevel,
        ?string $triggerReason,
        ?string $sourceSessionId,
    ): void {
        RankEventModel::create([
            'id'                => (string) Str::uuid(),
            'learner_id'        => $learnerId,
            'event_type'        => $eventType,
            'from_rank_tier'    => $fromRankTier,
            'from_rank_level'   => $fromRankLevel,
            'to_rank_tier'      => $toRankTier,
            'to_rank_level'     => $toRankLevel,
            'trigger_reason'    => $triggerReason,
            'source_session_id' => $sourceSessionId,
        ]);
    }

    public function updateInitialAssignment(
        string $learnerId,
        string $toRankTier,
        int $toRankLevel,
        string $triggerReason,
        ?string $sourceSessionId,
    ): void {
        RankEventModel::where('learner_id', $learnerId)
            ->where('event_type', RankEventType::InitialAssignment->value)
            ->update([
                'to_rank_tier'      => $toRankTier,
                'to_rank_level'     => $toRankLevel,
                'trigger_reason'    => $triggerReason,
                'source_session_id' => $sourceSessionId,
            ]);
    }

    public function findAllForLearner(string $learnerId): array
    {
        return RankEventModel::where('learner_id', $learnerId)
            ->orderBy('created_at')
            ->get()
            ->map(fn (RankEventModel $m) => new RankEventSummary(
                id:             $m->id,
                eventType:      $m->event_type->value,
                fromRankTier:   $m->from_rank_tier,
                fromRankLevel:  $m->from_rank_level,
                toRankTier:     $m->to_rank_tier,
                toRankLevel:    $m->to_rank_level,
                triggerReason:  $m->trigger_reason,
                createdAt:      $m->created_at,
            ))
            ->all();
    }
}
