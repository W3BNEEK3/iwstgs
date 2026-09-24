<?php
namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Str;
use Src\SimExecution\Domain\Diagnostic\DiagnosticPathway;
use Src\SimExecution\Domain\Diagnostic\DiagnosticSessionRepository;
use Src\SimExecution\Domain\Diagnostic\DiagnosticSessionSummary;
use Src\SimExecution\Domain\Diagnostic\DiagnosticStatus;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\DiagnosticSessionModel;

final class EloquentDiagnosticSessionRepository implements DiagnosticSessionRepository
{
    public function create(string $learnerId, DiagnosticPathway $pathway): string
    {
        $id = (string) Str::uuid();

        DiagnosticSessionModel::create([
            'id'         => $id,
            'learner_id' => $learnerId,
            'pathway'    => $pathway,
            'status'     => DiagnosticStatus::InProgress,
            'started_at' => now(),
        ]);

        return $id;
    }

    public function findInProgressForLearner(string $learnerId): ?DiagnosticSessionSummary
    {
        $model = DiagnosticSessionModel::where('learner_id', $learnerId)
            ->where('status', DiagnosticStatus::InProgress->value)
            ->latest('started_at')
            ->first();

        return $model ? $this->toSummary($model) : null;
    }

    public function findLatestForLearner(string $learnerId): ?DiagnosticSessionSummary
    {
        $model = DiagnosticSessionModel::where('learner_id', $learnerId)
            ->latest('started_at')
            ->first();

        return $model ? $this->toSummary($model) : null;
    }

    public function markComplete(string $id, string $assignedRankTier, int $assignedRankLevel): void
    {
        DiagnosticSessionModel::where('id', $id)->update([
            'status'              => DiagnosticStatus::Complete->value,
            'assigned_rank_tier'  => $assignedRankTier,
            'assigned_rank_level' => $assignedRankLevel,
            'completed_at'        => now(),
        ]);
    }

    private function toSummary(DiagnosticSessionModel $model): DiagnosticSessionSummary
    {
        return new DiagnosticSessionSummary(
            id:                $model->id,
            learnerId:         $model->learner_id,
            pathway:           $model->pathway,
            status:            $model->status,
            assignedRankTier:  $model->assigned_rank_tier,
            assignedRankLevel: $model->assigned_rank_level,
        );
    }
}
