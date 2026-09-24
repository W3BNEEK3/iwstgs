<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\SimExecution\Domain\Board\CardType;
use Src\SimExecution\Domain\Board\InjectedCardStatus;
use Src\SimExecution\Domain\Board\SuggestionType;
use Src\SimExecution\Domain\Board\VisualTreatment;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;

/**
 * Written by the Adaptive Engine's injectors (Phase 9): ConsequenceTaskInjector
 * and SuggestionTaskInjector, both outside this module (EvalEngine/AIMediation
 * respectively), via EloquentInjectedTaskCardRepository.
 */
class InjectedTaskCardModel extends Model
{
    use HasUuids;

    protected $table = 'injected_task_cards';

    // injected_at / resolved_at are the timestamp columns here — not created_at/updated_at.
    public $timestamps = false;

    protected $fillable = [
        'id', 'learner_session_id', 'learner_id', 'card_type', 'source_task_id',
        'injected_task_id', 'generated_task_content', 'target_sprint_id',
        'visual_treatment', 'status', 'suggestion_type', 'injected_at', 'resolved_at',
    ];

    protected $casts = [
        'card_type'               => CardType::class,
        'generated_task_content'  => 'array',
        'visual_treatment'        => VisualTreatment::class,
        'status'                  => InjectedCardStatus::class,
        'suggestion_type'         => SuggestionType::class,
        'injected_at'             => 'datetime',
        'resolved_at'             => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(LearnerSessionModel::class, 'learner_session_id');
    }

    public function sourceTask(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'source_task_id');
    }

    public function targetSprint(): BelongsTo
    {
        return $this->belongsTo(LearnerSprintModel::class, 'target_sprint_id');
    }
}
