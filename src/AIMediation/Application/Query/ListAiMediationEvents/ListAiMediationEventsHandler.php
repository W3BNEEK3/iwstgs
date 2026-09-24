<?php
namespace Src\AIMediation\Application\Query\ListAiMediationEvents;

use Illuminate\Support\Facades\DB;

final class ListAiMediationEventsHandler
{
    /** @return array{data: AiMediationEventView[], meta: array} paginated result */
    public function handle(ListAiMediationEventsQuery $query): array
    {
        $q = DB::table('aimediation_events as ae')
            ->leftJoin('learners as l', 'l.id', '=', 'ae.learner_id')
            ->leftJoin('users as u', 'u.id', '=', 'l.user_id')
            ->select([
                'ae.id', 'ae.created_at', 'ae.learner_id', 'ae.session_id',
                'ae.trigger_type', 'ae.trigger_source_id',
                'ae.action_taken', 'ae.action_detail',
                'ae.is_deterministic', 'ae.confidence_score',
                'u.name as learner_name',
            ])
            ->orderByDesc('ae.created_at');

        foreach ($query->filters as $key => $value) {
            match ($key) {
                'trigger_type' => $q->where('ae.trigger_type', $value),
                'action_taken' => $q->where('ae.action_taken', $value),
                'learner_id'   => $q->where('ae.learner_id', $value),
                'date_from'    => $q->whereDate('ae.created_at', '>=', $value),
                'date_to'      => $q->whereDate('ae.created_at', '<=', $value),
                default        => null,
            };
        }

        $paginator = $q->paginate($query->perPage);

        $data = collect($paginator->items())->map(fn ($row) => new AiMediationEventView(
            id:              $row->id,
            createdAt:       $row->created_at,
            learnerId:       $row->learner_id,
            learnerName:     $row->learner_name,
            sessionId:       $row->session_id,
            triggerType:     $row->trigger_type,
            triggerSourceId: $row->trigger_source_id,
            actionTaken:     $row->action_taken,
            actionDetail:    $row->action_detail ? json_decode($row->action_detail, true) : null,
            isDeterministic: (bool) $row->is_deterministic,
            confidenceScore: $row->confidence_score,
        ))->all();

        return ['data' => $data, 'paginator' => $paginator];
    }
}
