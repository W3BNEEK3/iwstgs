<?php
namespace Src\AIMediation\Application\Command\RecordConceptTagRecommendation;

use Illuminate\Support\Str;
use Src\AIMediation\Infrastructure\Persistence\Eloquent\Model\ConceptTagRecommendationModel;

/**
 * Gap 4 — Writes each Claude-suggested concept into concept_tag_recommendations
 * with status='pending' for curator review. Each suggestion is one row:
 * recommendation_type is always 'new_concept' and proposed_content carries
 * the concept_name, domain, and reason Claude returned.
 *
 * This closes the previously documented "nothing writes to this table" gap.
 */
final class RecordConceptTagRecommendationHandler
{
    public function handle(RecordConceptTagRecommendationCommand $command): void
    {
        foreach ($command->suggestions as $suggestion) {
            ConceptTagRecommendationModel::create([
                'id'                  => (string) Str::uuid(),
                'source_submission_id' => $command->submissionId,
                'task_id'             => $command->taskId,
                'recommendation_type' => 'new_concept',
                'proposed_content'    => [
                    'concept_name' => $suggestion['concept_name'],
                    'domain'       => $suggestion['domain'],
                    'reason'       => $suggestion['reason'],
                ],
                'status'              => 'pending',
                'curator_id'          => null,
                'curator_notes'       => null,
                'created_at'          => now(),
                'resolved_at'         => null,
            ]);
        }
    }
}
