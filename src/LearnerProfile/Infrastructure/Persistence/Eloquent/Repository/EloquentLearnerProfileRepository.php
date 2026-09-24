<?php

namespace Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\LearnerProfile\Domain\Profile\LearnerProfile;
use Src\LearnerProfile\Domain\Profile\LearnerProfileId;
use Src\LearnerProfile\Domain\Profile\LearnerProfileRepository;
use Src\LearnerProfile\Domain\Rank\RankEventType;
use Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Mapper\LearnerProfileMapper;
use Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Model\DimensionScoreModel;
use Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Model\LearnerProfileModel;
use Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Model\RankEventModel;

final class EloquentLearnerProfileRepository implements LearnerProfileRepository
{
    public function __construct(private readonly LearnerProfileMapper $mapper) {}

    public function save(LearnerProfile $profile): void
    {
        DB::transaction(function () use ($profile) {
            $data = $profile->toPrimitives();
            $dimensionScores = $data['dimension_scores'];
            unset($data['dimension_scores']); // not a learner_profiles column

            $model = LearnerProfileModel::updateOrCreate(['id' => $data['id']], $data);

            // wasRecentlyCreated is true only on the insert branch that just ran inside
            // updateOrCreate — never on the update branch. That is the whole guard: create
            // the six scores and the initial rank event once, on the row's first save,
            // and never touch them again on a later profile update.
            if (! $model->wasRecentlyCreated) {
                return;
            }

            foreach ($dimensionScores as $entry) {
                DimensionScoreModel::firstOrCreate(
                    ['learner_id' => $data['learner_id'], 'dimension_id' => $entry['dimension_id']],
                    [
                        'id'              => (string) Str::uuid(),
                        'tier'            => $entry['tier'],
                        'evidence_count'  => $entry['evidence_count'],
                        'last_updated_at' => now(),
                    ],
                );
            }

            RankEventModel::firstOrCreate(
                ['learner_id' => $data['learner_id'], 'event_type' => RankEventType::InitialAssignment->value],
                [
                    'id'                => (string) Str::uuid(),
                    'from_rank_tier'    => null,
                    'from_rank_level'   => null,
                    'to_rank_tier'      => $data['current_rank_tier'],
                    'to_rank_level'     => $data['current_rank_level'],
                    'trigger_reason'    => 'Default starting rank assigned at profile creation, pending diagnostic assessment.',
                    'source_session_id' => null,
                ],
            );
        });
    }

    public function findByLearnerId(string $learnerId): ?LearnerProfile
    {
        $model = LearnerProfileModel::where('learner_id', $learnerId)->first();
        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function findById(LearnerProfileId $id): ?LearnerProfile
    {
        $model = LearnerProfileModel::find((string) $id);
        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function existsForLearner(string $learnerId): bool
    {
        return LearnerProfileModel::where('learner_id', $learnerId)->exists();
    }
}
