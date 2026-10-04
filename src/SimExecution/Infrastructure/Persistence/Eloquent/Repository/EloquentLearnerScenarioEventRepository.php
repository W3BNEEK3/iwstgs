<?php
namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\SimExecution\Domain\Story\LearnerScenarioEventRepository;
use Src\SimExecution\Domain\Story\StoryMessage;

final class EloquentLearnerScenarioEventRepository implements LearnerScenarioEventRepository
{
    public function recordFired(string $learnerSessionId, string $scenarioEventId, array $result): bool
    {
        // The unique (session, event) index makes this safe against two requests firing at once.
        return DB::table('learner_scenario_events')->insertOrIgnore([
            'id'                 => (string) Str::uuid(),
            'learner_session_id' => $learnerSessionId,
            'scenario_event_id'  => $scenarioEventId,
            'fired_at'           => now(),
            'result'             => json_encode($result),
        ]) === 1;
    }

    public function feed(string $learnerSessionId): array
    {
        return DB::table('learner_scenario_events')
            ->where('learner_session_id', $learnerSessionId)
            ->orderByDesc('fired_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (object $row) {
                $r = json_decode($row->result ?? '{}', true) ?? [];

                return new StoryMessage(
                    eventType:  $r['event_type'] ?? 'stakeholder_message',
                    sender:     $r['sender'] ?? 'The team',
                    senderRole: $r['role'] ?? null,
                    text:       $r['text'] ?? '',
                    firedAt:    (string) $row->fired_at,
                );
            })
            ->all();
    }
}
