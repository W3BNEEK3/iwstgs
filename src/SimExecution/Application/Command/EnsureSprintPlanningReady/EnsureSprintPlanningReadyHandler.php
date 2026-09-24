<?php
namespace Src\SimExecution\Application\Command\EnsureSprintPlanningReady;

use Illuminate\Support\Facades\DB;
use Src\LearnerProfile\Application\Query\GetLearnerRank\GetLearnerRankQuery;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Domain\Backlog\BacklogPriority;
use Src\SimExecution\Domain\Backlog\LearnerBacklogItem;
use Src\SimExecution\Domain\Backlog\LearnerBacklogItemId;
use Src\SimExecution\Domain\Backlog\LearnerBacklogItemRepository;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Domain\Exceptions\InvalidSessionTransitionException;
use Src\SimExecution\Domain\Exceptions\LearnerNotFoundException;
use Src\SimExecution\Domain\Exceptions\SessionNotFoundException;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;
use Src\SimExecution\Domain\Session\SessionStatus;
use Src\SimExecution\Domain\Sprint\LearnerSprint;
use Src\SimExecution\Domain\Sprint\LearnerSprintId;
use Src\SimExecution\Domain\Sprint\LearnerSprintRepository;
use Src\SimExecution\Domain\Sprint\SprintGoalPolicy;
use Src\SimExecution\Domain\Sprint\SprintGoalSource;
use Src\SimExecution\Domain\Sprint\SprintStatus;
use Src\Simulation\Application\Query\ListBacklogItemTemplatesByProject\ListBacklogItemTemplatesByProjectQuery;
use Src\Simulation\Application\Query\ListTasksByScenario\ListTasksByScenarioQuery;
use Src\Simulation\Application\Query\ListVaultItemsByProject\ListVaultItemsByProjectQuery;
use Src\Simulation\Domain\Backlog\BacklogItemTemplateSummary;
use Src\Simulation\Domain\Task\Task;
use Src\Simulation\Domain\Vault\ArtifactVaultItem;

/**
 * Idempotent setup step run every time a learner opens sprint planning:
 * seeds their personal backlog copy from backlog_item_templates for the
 * current scenario (see seedBacklogForCurrentScenario), and opens a new sprint in planning once the previous one has been
 * submitted (or none exists yet). A sprint already in planning/active is
 * left untouched.
 */
final class EnsureSprintPlanningReadyHandler
{
    public function __construct(
        private readonly LearnerRepository $learners,
        private readonly LearnerSessionRepository $sessions,
        private readonly LearnerBacklogItemRepository $backlogItems,
        private readonly LearnerSprintRepository $sprints,
        private readonly QueryBus $queryBus,
    ) {}

    public function handle(EnsureSprintPlanningReadyCommand $command): void
    {
        $learner = $command->userId !== null ? $this->learners->findByUserId($command->userId) : null;
        if ($learner === null) {
            throw new LearnerNotFoundException();
        }

        $session = $this->sessions->findByLearnerAndProject($learner->id(), $command->projectId);
        if ($session === null) {
            throw new SessionNotFoundException();
        }

        if ($session->status() !== SessionStatus::Active) {
            throw new InvalidSessionTransitionException('induction must be completed before sprint planning is available');
        }

        DB::transaction(function () use ($learner, $session, $command) {
            $this->seedBacklogForCurrentScenario($learner->id(), $session->id(), $command->projectId, $session->currentScenarioId());

            $latest = $this->sprints->findLatestForSession($session->id());
            $needsNewSprint = $latest === null
                || in_array($latest->status(), [SprintStatus::Submitted, SprintStatus::Evaluated], true);

            if (! $needsNewSprint) {
                return;
            }

            $rank = $this->queryBus->ask(new GetLearnerRankQuery($learner->id()));
            $rankTier = $rank?->rankTier ?? 'Junior';
            $rankLevel = $rank?->rankLevel ?? 1;
            $goalSource = SprintGoalPolicy::resolve($rankTier, $rankLevel);

            $initialGoal = null;
            if ($goalSource !== SprintGoalSource::LearnerDefined) {
                $initialGoal = $this->findSprintGoalTemplateContent($command->projectId, $rankTier);
            }

            $sprint = LearnerSprint::plan(
                id:               LearnerSprintId::generate(),
                learnerSessionId: $session->id(),
                learnerId:        $learner->id(),
                projectId:        $command->projectId,
                sprintNumber:     $this->sprints->countForSession($session->id()) + 1,
                sprintGoalSource: $goalSource,
                sprintGoal:       $initialGoal,
            );
            $this->sprints->save($sprint);

            foreach ($sprint->releaseEvents() as $event) {
                event($event);
            }
        });
    }

    /**
     * Only items whose task belongs to the learner's current scenario are
     * seeded, since SubmitTaskHandler rejects tasks from any other scenario.
     * Items for later scenarios are topped up here once the session advances.
     * Unlinked tickets (no task) are realistic backlog noise, seeded once.
     */
    private function seedBacklogForCurrentScenario(string $learnerId, string $sessionId, string $projectId, ?string $scenarioId): void
    {
        $alreadySeeded = $this->backlogItems->seededTemplateIdsForSession($sessionId);
        $isFirstSeed = $alreadySeeded === [];

        $currentScenarioTaskIds = [];
        if ($scenarioId !== null) {
            /** @var Task[] $tasks */
            $tasks = $this->queryBus->ask(new ListTasksByScenarioQuery($scenarioId));
            $currentScenarioTaskIds = array_map(fn (Task $t) => $t->id(), $tasks);
        }

        /** @var BacklogItemTemplateSummary[] $templates */
        $templates = $this->queryBus->ask(new ListBacklogItemTemplatesByProjectQuery($projectId));
        foreach ($templates as $template) {
            if (in_array($template->id, $alreadySeeded, true)) {
                continue;
            }

            $inScope = $template->taskId === null
                ? $isFirstSeed
                : in_array($template->taskId, $currentScenarioTaskIds, true);
            if (! $inScope) {
                continue;
            }

            $this->backlogItems->save(LearnerBacklogItem::seedFromTemplate(
                id:               LearnerBacklogItemId::generate(),
                learnerSessionId: $sessionId,
                learnerId:        $learnerId,
                templateItemId:   $template->id,
                priority:         BacklogPriority::from($template->defaultPriority),
            ));
        }
    }

    /**
     * Looks for a project-authored vault item of document_type
     * sprint_goal_template (Integration Spec §15), preferring one whose
     * rank_gate matches the learner's tier, falling back to an ungated one.
     * Returned as a plain string via toPrimitives() — same access pattern
     * the existing admin vault view already uses; ArtifactVaultItem has no
     * public content getter.
     */
    private function findSprintGoalTemplateContent(string $projectId, string $rankTier): ?string
    {
        /** @var ArtifactVaultItem[] $items */
        $items = $this->queryBus->ask(new ListVaultItemsByProjectQuery($projectId));

        $fallback = null;
        foreach ($items as $item) {
            $primitives = $item->toPrimitives();
            if ($primitives['document_type'] !== 'sprint_goal_template') {
                continue;
            }
            if ($primitives['rank_gate'] === $rankTier) {
                return $primitives['content'];
            }
            if ($primitives['rank_gate'] === null && $fallback === null) {
                $fallback = $primitives['content'];
            }
        }

        return $fallback;
    }
}
