<?php

namespace Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\LearnerProfile\Domain\Profile\CacLevel;
use Src\LearnerProfile\Domain\Profile\RankTier;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerModel;

class LearnerProfileModel extends Model
{
    use HasUuids;

    protected $table = 'learner_profiles';

    // Only updated_at exists, no created_at — 5a Reconciliation #5. MySQL's
    // useCurrentOnUpdate() default keeps it current on every row change, so there is
    // nothing left for Eloquent to manage; false is correct, not a workaround.
    public $timestamps = false;

    protected $fillable = [
        'id', 'learner_id', 'current_rank_tier', 'current_rank_level',
        'cac_complexity', 'cac_autonomy', 'cac_context_fidelity',
        'mismatch_flag_active', 'failure_streak', 'success_streak',
    ];

    protected $casts = [
        'current_rank_tier'     => RankTier::class,
        'current_rank_level'    => 'integer',
        'cac_complexity'        => CacLevel::class,
        'cac_autonomy'          => CacLevel::class,
        'cac_context_fidelity'  => CacLevel::class,
        'mismatch_flag_active'  => 'boolean',
        'failure_streak'        => 'integer',
        'success_streak'        => 'integer',
    ];

    public function learner(): BelongsTo
    {
        return $this->belongsTo(LearnerModel::class, 'learner_id');
    }

    // Keyed on the shared learner_id column, not on this row's own id — the FK on
    // each child table points at learners.id rather than nesting under learner_profiles.id.
    public function dimensionScores(): HasMany
    {
        return $this->hasMany(DimensionScoreModel::class, 'learner_id', 'learner_id');
    }

    public function rankEvents(): HasMany
    {
        return $this->hasMany(RankEventModel::class, 'learner_id', 'learner_id');
    }

    public function conceptMasteryRecords(): HasMany
    {
        return $this->hasMany(ConceptMasteryRecordModel::class, 'learner_id', 'learner_id');
    }
}
