<?php
namespace Src\Submission\Application\Query\GetSubmissionForm;

use Src\LearnerProfile\Application\Query\GetLearnerRank\GetLearnerRankQuery;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Query\GetLearnerIdForUser\GetLearnerIdForUserQuery;
use Src\SimExecution\Application\Query\GetLearnerSession\GetLearnerSessionQuery;
use Src\SimExecution\Application\Query\IsTaskInjectedForSession\IsTaskInjectedForSessionQuery;
use Src\Simulation\Application\Query\GetScenario\GetScenarioQuery;
use Src\Simulation\Application\Query\GetTask\GetTaskQuery;
use Src\Simulation\Domain\Scenario\ReferenceMaterial;
use Src\Simulation\Domain\Scenario\ScenarioTemplate;
use Src\Simulation\Domain\Task\CacVariant;
use Src\Simulation\Domain\Task\ExpectedDeliverable;
use Src\Simulation\Domain\Task\GuidancePrompt;
use Src\Simulation\Domain\Task\Task;
use Src\Submission\Domain\Submission\SubmissionPackageRepository;

final class GetSubmissionFormHandler
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly SubmissionPackageRepository $packages,
    ) {}

    public function handle(GetSubmissionFormQuery $query): ?SubmissionFormView
    {
        if ($query->userId === null) {
            return null;
        }

        $learnerId = $this->queryBus->ask(new GetLearnerIdForUserQuery($query->userId));
        if ($learnerId === null) {
            return null;
        }

        $session = $this->queryBus->ask(new GetLearnerSessionQuery($query->sessionId));
        if ($session === null || $session->learnerId !== $learnerId) {
            return null;
        }

        if ($session->status !== 'active' && $session->status !== 'diagnostic') {
            return null;
        }

        /** @var Task|null $task */
        $task = $this->queryBus->ask(new GetTaskQuery($query->taskId));
        if ($task === null || ! $task->isPublished()) {
            return null;
        }

        $taskPrimitives = $task->toPrimitives();
        // A task can only be submitted for while it's the session's current scenario — the
        // scope a learner is actually meant to be working in right now — or when it was
        // injected onto this session as a consequence/suggestion card.
        if ($taskPrimitives['scenario_id'] !== $session->currentScenarioId
            && ! $this->queryBus->ask(new IsTaskInjectedForSessionQuery($query->sessionId, $query->taskId))) {
            return null;
        }

        $deliverables = array_map(
            fn (ExpectedDeliverable $d) => new DeliverableFieldView(
                id:          $d->id(),
                type:        $d->toPrimitives()['type'],
                label:       $d->toPrimitives()['label'],
                description: $d->toPrimitives()['description'],
                isRequired:  $d->toPrimitives()['is_required'],
            ),
            $task->expectedDeliverables(),
        );

        // BLD §4.2 — diagnostic tasks are always presented at fixed mid/mid/mid,
        // never against the learner's (still-default, or simply irrelevant)
        // profile CAC — same special case SubmitTaskHandler already applies
        // when stamping cac_*_at_sub on the submission itself.
        $isDiagnostic = $session->status === 'diagnostic';
        if ($isDiagnostic) {
            [$cacComplexity, $cacAutonomy, $cacContextFidelity] = ['mid', 'mid', 'mid'];
        } else {
            $rank = $this->queryBus->ask(new GetLearnerRankQuery($learnerId));
            $cacComplexity = $rank?->cacComplexity ?? 'low';
            $cacAutonomy = $rank?->cacAutonomy ?? 'mid';
            $cacContextFidelity = $rank?->cacContextFidelity ?? 'low';
        }

        $variant = null;
        foreach ($task->cacVariants() as $candidate) {
            /** @var CacVariant $candidate */
            if ($candidate->toPrimitives()['complexity_level'] === $cacComplexity) {
                $variant = $candidate;
                break;
            }
        }

        $variantPrimitives = $variant?->toPrimitives();

        $guidancePrompts = array_values(array_filter(
            $task->guidancePrompts(),
            fn (GuidancePrompt $p) => in_array($p->toPrimitives()['autonomy_level_filter'], [null, $cacAutonomy], true),
        ));
        usort($guidancePrompts, fn (GuidancePrompt $a, GuidancePrompt $b) => $a->toPrimitives()['display_order'] <=> $b->toPrimitives()['display_order']);

        return new SubmissionFormView(
            taskId:              $task->id(),
            taskTitle:           $taskPrimitives['title'],
            taskBrief:           $taskPrimitives['task_brief'],
            timeLimitMinutes:    $taskPrimitives['time_limit_minutes'],
            nextAttemptNumber:   $this->packages->countAttempts($query->sessionId, $query->taskId) + 1,
            deliverables:        $deliverables,
            resolvedScenarioText: $variantPrimitives['scenario_text'] ?? $taskPrimitives['task_brief'],
            scaffoldingHint:     $variantPrimitives["scaffolding_text_{$cacAutonomy}"] ?? null,
            contextNote:         $variantPrimitives["context_text_{$cacContextFidelity}"] ?? null,
            guidancePrompts:     array_map(
                fn (GuidancePrompt $p) => new GuidancePromptFieldView(
                    promptText:    $p->toPrimitives()['prompt_text'],
                    deliveryMode:  $p->toPrimitives()['delivery_mode'],
                ),
                $guidancePrompts,
            ),
            cacComplexity:       $cacComplexity,
            cacAutonomy:         $cacAutonomy,
            cacContextFidelity:  $cacContextFidelity,
            referenceMaterials:  $this->referenceMaterials($taskPrimitives['scenario_id']),
        );
    }

    /** @return array<array{type: string, title: string, content: string}> */
    private function referenceMaterials(string $scenarioId): array
    {
        /** @var ScenarioTemplate|null $scenario */
        $scenario = $this->queryBus->ask(new GetScenarioQuery($scenarioId));

        return array_map(
            fn (ReferenceMaterial $m) => ['type' => $m->type()->value, 'title' => $m->title(), 'content' => $m->content()],
            $scenario?->referenceMaterials() ?? [],
        );
    }
}
