<?php
namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Facades\DB;
use Src\Simulation\Domain\Build\BuildContentRepository;
use Src\Simulation\Domain\Build\ScenarioEvent;
use Src\Simulation\Domain\Build\StackVariant;
use Src\Simulation\Domain\Build\TaskVariantSpec;

final class EloquentBuildContentRepository implements BuildContentRepository
{
    public function variantsForProject(string $projectId, bool $publishedOnly): array
    {
        return DB::table('project_stack_variants')
            ->where('project_id', $projectId)
            ->when($publishedOnly, fn ($q) => $q->where('is_published', true))
            ->orderBy('difficulty')->orderBy('name')
            ->get()
            ->map(fn (object $r) => $this->variant($r))
            ->all();
    }

    public function findVariant(string $variantId): ?StackVariant
    {
        $row = DB::table('project_stack_variants')->where('id', $variantId)->first();

        return $row === null ? null : $this->variant($row);
    }

    public function findSpec(string $taskId, string $variantId): ?TaskVariantSpec
    {
        $row = DB::table('task_variant_specs')->where('task_id', $taskId)->where('stack_variant_id', $variantId)->first();

        return $row === null ? null : new TaskVariantSpec(
            taskId:          $row->task_id,
            stackVariantId:  $row->stack_variant_id,
            briefAddendum:   $row->brief_addendum,
            acceptanceTests: json_decode($row->acceptance_tests, true) ?? [],
            hints:           $row->hints === null ? null : json_decode($row->hints, true),
            referenceTag:    $row->reference_tag,
        );
    }

    public function acceptanceTestsByTask(string $variantId): array
    {
        return DB::table('task_variant_specs')
            ->where('stack_variant_id', $variantId)
            ->pluck('acceptance_tests', 'task_id')
            ->map(fn ($json) => json_decode($json, true) ?? [])
            ->all();
    }

    public function eventsForScenario(string $scenarioId): array
    {
        return DB::table('scenario_events')
            ->where('scenario_id', $scenarioId)
            ->orderBy('display_order')
            ->get()
            ->map(fn (object $r) => new ScenarioEvent(
                id:            $r->id,
                scenarioId:    $r->scenario_id,
                trigger:       $r->trigger,
                triggerTaskId: $r->trigger_task_id,
                eventType:     $r->event_type,
                roleTags:      $r->role_tags === null ? null : json_decode($r->role_tags, true),
                payload:       json_decode($r->payload, true) ?? [],
                displayOrder:  (int) $r->display_order,
            ))
            ->all();
    }

    private function variant(object $r): StackVariant
    {
        return new StackVariant(
            id:             $r->id,
            projectId:      $r->project_id,
            key:            $r->key,
            name:           $r->name,
            languages:      json_decode($r->languages, true) ?? [],
            difficulty:     (int) $r->difficulty,
            minRankTier:    $r->min_rank_tier,
            minRankLevel:   $r->min_rank_level === null ? null : (int) $r->min_rank_level,
            templateRepo:   $r->template_repo,
            referenceRepo:  $r->reference_repo,
            acceptanceRef:  $r->acceptance_ref,
            workflowSha256: $r->workflow_sha256,
            setupNotes:     $r->setup_notes,
            isPublished:    (bool) $r->is_published,
        );
    }
}
