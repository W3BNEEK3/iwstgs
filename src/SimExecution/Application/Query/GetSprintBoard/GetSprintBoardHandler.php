<?php
namespace Src\SimExecution\Application\Query\GetSprintBoard;

use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Domain\Backlog\BacklogItemStatus;
use Src\SimExecution\Domain\Backlog\LearnerBacklogItem;
use Src\SimExecution\Domain\Backlog\LearnerBacklogItemRepository;
use Src\SimExecution\Domain\Board\InjectedTaskCardRepository;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;
use Src\SimExecution\Domain\Session\SessionStatus;
use Src\SimExecution\Domain\Sprint\LearnerSprintRepository;
use Src\SimExecution\Domain\Sprint\SprintStatus;
use Src\Simulation\Application\Query\GetScenario\GetScenarioQuery;
use Src\Simulation\Application\Query\GetTask\GetTaskQuery;
use Src\Simulation\Application\Query\ListBacklogItemTemplatesByProject\ListBacklogItemTemplatesByProjectQuery;
use Src\Simulation\Application\Query\ListVaultItemsByProject\ListVaultItemsByProjectQuery;
use Src\Simulation\Domain\Backlog\BacklogItemTemplateSummary;
use Src\Simulation\Domain\Scenario\ReferenceMaterial;
use Src\Simulation\Domain\Scenario\ScenarioTemplate;
use Src\Simulation\Domain\Task\Task;
use Src\Simulation\Domain\Vault\ArtifactVaultItem;
use Src\Submission\Application\Query\CountSubmissionAttempts\CountSubmissionAttemptsQuery;

final class GetSprintBoardHandler
{
    public function __construct(
        private readonly LearnerRepository $learners,
        private readonly LearnerSessionRepository $sessions,
        private readonly LearnerSprintRepository $sprints,
        private readonly LearnerBacklogItemRepository $backlogItems,
        private readonly InjectedTaskCardRepository $injectedCards,
        private readonly QueryBus $queryBus,
    ) {}

    public function handle(GetSprintBoardQuery $query): ?SprintBoardView
    {
        $learner = $query->userId !== null ? $this->learners->findByUserId($query->userId) : null;
        if ($learner === null) {
            return null;
        }

        $session = $this->sessions->findByLearnerAndProject($learner->id(), $query->projectId);
        if ($session === null || $session->status() !== SessionStatus::Active || $session->currentSprintId() === null) {
            return null;
        }

        $sprint = $this->sprints->findById($session->currentSprintId());
        if ($sprint === null || $sprint->status() !== SprintStatus::Active) {
            return null;
        }

        /** @var BacklogItemTemplateSummary[] $templates */
        $templates = $this->queryBus->ask(new ListBacklogItemTemplatesByProjectQuery($query->projectId));
        $templatesById = [];
        foreach ($templates as $template) {
            $templatesById[$template->id] = $template;
        }

        $columns = ['in_sprint' => [], 'in_progress' => [], 'done' => [], 'blocked' => []];
        foreach ($this->backlogItems->findAllForSprint($sprint->id()) as $item) {
            if ($item->isInjected()) {
                $view = $this->buildInjectedItemView($item, $session->id());
            } else {
                $template = $templatesById[$item->templateItemId()] ?? null;
                $view = new BacklogItemBoardView(
                    id:              $item->id(),
                    title:           $template?->title ?? '(item content unavailable)',
                    description:     $template?->description,
                    priority:        $item->priority()->value,
                    status:          $item->status()->value,
                    isInjected:      false,
                    taskId:          $template?->taskId,
                    visualTreatment: null,
                    submissionCount: $template?->taskId !== null
                        ? $this->queryBus->ask(new CountSubmissionAttemptsQuery($session->id(), $template->taskId))
                        : 0,
                );
            }

            $key = $item->status()->value;
            if (isset($columns[$key])) {
                $columns[$key][] = $view;
            }
        }

        $scenarioTitle = null;
        $referenceMaterials = [];
        if ($session->currentScenarioId() !== null) {
            /** @var ScenarioTemplate|null $scenario */
            $scenario = $this->queryBus->ask(new GetScenarioQuery($session->currentScenarioId()));
            if ($scenario !== null) {
                $scenarioTitle = $scenario->title();
                $referenceMaterials = array_map(
                    fn (ReferenceMaterial $m) => new ReferenceMaterialBoardView(
                        type:    $m->type()->value,
                        title:   $m->title(),
                        content: $m->content(),
                    ),
                    $scenario->referenceMaterials(),
                );
            }
        }

        /** @var ArtifactVaultItem[] $vault */
        $vault = $this->queryBus->ask(new ListVaultItemsByProjectQuery($query->projectId));
        $vaultViews = [];
        foreach ($vault as $vaultItem) {
            $primitives = $vaultItem->toPrimitives();
            if (! $primitives['is_reference_doc']) {
                continue;
            }
            $vaultViews[] = new VaultItemBoardView(
                id:           $primitives['id'],
                title:        $primitives['title'],
                documentType: $primitives['document_type'],
            );
        }

        return new SprintBoardView(
            sessionId:           $session->id(),
            sprintId:            $sprint->id(),
            sprintNumber:        $sprint->sprintNumber(),
            sprintGoal:          $sprint->sprintGoal() ?? '',
            scenarioTitle:       $scenarioTitle,
            toDo:                $columns['in_sprint'],
            inProgress:          $columns['in_progress'],
            done:                $columns['done'],
            blocked:             $columns['blocked'],
            vaultItems:          $vaultViews,
            referenceMaterials:  $referenceMaterials,
        );
    }

    /**
     * An injected item has no backlog_item_template (template_item_id is
     * null — Phase 9) — its real content lives on the injected_task_cards
     * row instead, pointing at the actual Task assigned as the consequence/
     * suggestion. visual_treatment drives the amber/teal card styling from
     * Integration Spec §13.4.
     */
    private function buildInjectedItemView(LearnerBacklogItem $item, string $learnerSessionId): BacklogItemBoardView
    {
        $card = $item->injectedCardId() !== null ? $this->injectedCards->findById($item->injectedCardId()) : null;
        $task = $card?->injectedTaskId !== null ? $this->queryBus->ask(new GetTaskQuery($card->injectedTaskId)) : null;
        /** @var Task|null $task */

        return new BacklogItemBoardView(
            id:              $item->id(),
            title:           $task?->toPrimitives()['title'] ?? '(injected task unavailable)',
            description:     $task?->toPrimitives()['task_brief'],
            priority:        $item->priority()->value,
            status:          $item->status()->value,
            isInjected:      true,
            taskId:          $card?->injectedTaskId,
            visualTreatment: $card?->visualTreatment,
            submissionCount: $card?->injectedTaskId !== null
                ? $this->queryBus->ask(new CountSubmissionAttemptsQuery($learnerSessionId, $card->injectedTaskId))
                : 0,
            incidentTicketText: $card?->generatedTaskContent['incident_ticket_text'] ?? null,
        );
    }
}
