<?php
namespace Src\AIMediation\Application\Query\ListConceptTagRecommendations;

use Illuminate\Support\Facades\DB;

final class ListConceptTagRecommendationsHandler
{
    public function handle(ListConceptTagRecommendationsQuery $query): array
    {
        return DB::table('concept_tag_recommendations as ctr')
            ->leftJoin('tasks as t', 't.id', '=', 'ctr.task_id')
            ->where('ctr.status', $query->status)
            ->select([
                'ctr.id', 'ctr.status', 'ctr.recommendation_type',
                'ctr.proposed_content', 'ctr.created_at',
                'ctr.curator_notes',
                't.title as task_title',
            ])
            ->orderByDesc('ctr.created_at')
            ->get()
            ->map(fn ($row) => [
                'id'               => $row->id,
                'status'           => $row->status,
                'taskTitle'        => $row->task_title ?? '—',
                'proposedContent'  => json_decode($row->proposed_content, true),
                'createdAt'        => $row->created_at,
                'curatorNotes'     => $row->curator_notes,
            ])
            ->all();
    }
}
